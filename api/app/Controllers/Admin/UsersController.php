<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Resources\UserResource;
use App\Services\Users\UserAdminService;
use App\Validation\StoreUserValidator;
use App\Validation\UpdateUserValidator;

class UsersController extends Controller
{
    public function __construct(
        private readonly UserAdminService $users = new UserAdminService(),
        private readonly StoreUserValidator $storeValidator = new StoreUserValidator(),
        private readonly UpdateUserValidator $updateValidator = new UpdateUserValidator()
    ) {
    }

    public function index(): never
    {
        $this->ok($this->users->paginate(Request::query()), 'Списък с потребители.');
    }

    public function show(string $id): never
    {
        $user = $this->users->find($this->id($id), true);

        $this->ok(['user' => UserResource::toAdminArray($user)]);
    }

    public function store(): never
    {
        $payload = $this->storeValidator->validate(Request::input());
        $user = $this->users->create($payload);

        $this->created(['user' => UserResource::toAdminArray($user)], 'Потребителят е създаден.');
    }

    public function update(string $id): never
    {
        $user = $this->users->find($this->id($id));
        $payload = $this->updateValidator->validate(Request::input(), $user->id);
        $user = $this->users->update($user, $payload);

        $this->ok(['user' => UserResource::toAdminArray($user)], 'Потребителят е обновен.');
    }

    public function destroy(string $id): never
    {
        $this->users->delete($this->users->find($this->id($id)));

        $this->ok([], 'Потребителят е изтрит.');
    }

    public function restore(string $id): never
    {
        $user = $this->users->restore($this->id($id));

        $this->ok(['user' => UserResource::toAdminArray($user)], 'Потребителят е възстановен.');
    }

    private function id(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            $this->error('Потребителят не е намерен.', 404);
        }

        return (int) $id;
    }
}
