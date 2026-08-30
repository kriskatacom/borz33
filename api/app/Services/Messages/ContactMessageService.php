<?php

declare(strict_types=1);

namespace App\Services\Messages;

use App\Exceptions\AuthException;
use App\Models\ContactMessage;
use App\Resources\ContactMessageResource;
use Illuminate\Database\Eloquent\Builder;

class ContactMessageService
{
    /** @param array<string, mixed> $filters */
    public function paginate(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 20)));
        $query = ContactMessage::query();
        $q = trim((string) ($filters['q'] ?? ''));
        $status = trim((string) ($filters['status'] ?? 'all'));

        if ($q !== '') {
            $query->where(static function (Builder $builder) use ($q): void {
                $like = '%' . $q . '%';
                $builder->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('subject', 'like', $like)->orWhere('message', 'like', $like);
            });
        }
        if ($status === 'unread') $query->whereNull('read_at');
        if ($status === 'read') $query->whereNotNull('read_at');

        $total = (clone $query)->count();
        $messages = $query->orderByRaw('read_at IS NULL DESC')->orderByDesc('created_at')->orderByDesc('id')->forPage($page, $perPage)->get();

        return [
            'messages' => $messages->map(static fn (ContactMessage $message): array => ContactMessageResource::toArray($message))->all(),
            'unread_count' => ContactMessage::query()->whereNull('read_at')->count(),
            'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => max(1, (int) ceil($total / $perPage))],
        ];
    }

    public function find(int $id): ContactMessage
    {
        $message = ContactMessage::query()->find($id);
        if ($message === null) throw new AuthException('Съобщението не е намерено.', 404);
        return $message;
    }

    public function mark(ContactMessage $message, bool $read): ContactMessage
    {
        $message->read_at = $read ? ($message->read_at ?? new \DateTimeImmutable()) : null;
        $message->save();
        return $message->fresh() ?? $message;
    }
}
