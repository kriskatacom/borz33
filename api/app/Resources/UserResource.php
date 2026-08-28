<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\User;

class UserResource
{
    /** @return array<string, mixed> */
    public static function toArray(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    public static function toAdminArray(User $user): array
    {
        return [
            ...self::toArray($user),
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'last_login_ip' => $user->last_login_ip,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
            'deleted_at' => $user->deleted_at?->toIso8601String(),
        ];
    }

    /** @param iterable<int, User> $users */
    public static function collection(iterable $users): array
    {
        $items = [];

        foreach ($users as $user) {
            $items[] = self::toAdminArray($user);
        }

        return $items;
    }
}
