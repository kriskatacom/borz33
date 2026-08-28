<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\Core\Auth;
use App\Exceptions\AuthException;
use App\Models\User;
use App\Resources\UserResource;
use App\Services\Auth\PasswordHasher;
use App\Services\Auth\TokenService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class UserAdminService
{
    public function __construct(
        private readonly PasswordHasher $passwordHasher = new PasswordHasher(),
        private readonly TokenService $tokenService = new TokenService()
    ) {
    }

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(50, max(1, (int) ($filters['per_page'] ?? 20)));
        $query = $this->filteredQuery($filters);
        $total = (clone $query)->count();
        $users = $query
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get();

        return [
            'users' => UserResource::collection($users),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
        ];
    }

    public function find(int $id, bool $withTrashed = false): User
    {
        $query = $withTrashed ? User::withTrashed() : User::query();
        $user = $query->find($id);

        if ($user === null) {
            throw new AuthException('Потребителят не е намерен.', 404);
        }

        return $user;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): User
    {
        $user = new User();
        $user->forceFill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $this->passwordHasher->hash((string) $data['password']),
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'is_active' => (bool) $data['is_active'],
            'email_verified_at' => Carbon::now(),
        ]);
        $user->save();

        return $user->fresh() ?? $user;
    }

    /** @param array<string, mixed> $data */
    public function update(User $user, array $data): User
    {
        $actor = Auth::requireUser();
        $becomesInactive = !((bool) $data['is_active']);
        $becomesCustomer = $data['role'] === User::ROLE_CUSTOMER;
        $wasActiveAdmin = $user->isAdmin() && $user->isActive();

        if ($actor->id === $user->id && ($becomesInactive || $becomesCustomer)) {
            throw new AuthException('Не можете да деактивирате или смените ролята на собствения си профил.');
        }

        if ($wasActiveAdmin && ($becomesInactive || $becomesCustomer) && $this->activeAdminCount($user->id) === 0) {
            throw new AuthException('Трябва да остане поне един активен администратор.');
        }

        $payload = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'is_active' => (bool) $data['is_active'],
        ];

        if (isset($data['password']) && is_string($data['password']) && $data['password'] !== '') {
            $payload['password'] = $this->passwordHasher->hash($data['password']);
        }

        $user->forceFill($payload)->save();

        if ($becomesInactive || isset($payload['password'])) {
            $this->tokenService->revokeAllForUser($user);
        }

        return $user->fresh() ?? $user;
    }

    public function delete(User $user): void
    {
        $actor = Auth::requireUser();

        if ($actor->id === $user->id) {
            throw new AuthException('Не можете да изтриете собствения си профил.');
        }

        if ($user->isAdmin() && $user->isActive() && $this->activeAdminCount($user->id) === 0) {
            throw new AuthException('Трябва да остане поне един активен администратор.');
        }

        $this->tokenService->revokeAllForUser($user);
        $user->delete();
    }

    public function restore(int $id): User
    {
        $user = $this->find($id, true);

        if ($user->deleted_at === null) {
            throw new AuthException('Потребителят не е изтрит.');
        }

        $user->restore();

        return $user->fresh() ?? $user;
    }

    /** @param array<string, mixed> $filters */
    private function filteredQuery(array $filters): Builder
    {
        $status = (string) ($filters['status'] ?? 'all');
        $query = match ($status) {
            'deleted' => User::onlyTrashed(),
            'inactive' => User::query()->where('is_active', false),
            'active' => User::query()->where('is_active', true),
            default => User::query(),
        };

        $role = (string) ($filters['role'] ?? '');

        if (in_array($role, [User::ROLE_ADMIN, User::ROLE_CUSTOMER], true)) {
            $query->where('role', $role);
        }

        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(static function (Builder $builder) use ($like): void {
                $builder
                    ->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        return $query;
    }

    private function activeAdminCount(?int $exceptId = null): int
    {
        $query = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('is_active', true);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->count();
    }
}
