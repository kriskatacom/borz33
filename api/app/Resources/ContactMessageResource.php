<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\ContactMessage;

class ContactMessageResource
{
    /** @return array<string, mixed> */
    public static function toArray(ContactMessage $message): array
    {
        return [
            'id' => $message->id,
            'user_id' => $message->user_id,
            'name' => $message->name,
            'email' => $message->email,
            'phone' => $message->phone,
            'subject' => $message->subject,
            'message' => $message->message,
            'email_sent' => $message->email_sent,
            'read_at' => $message->read_at?->toIso8601String(),
            'created_at' => $message->created_at?->toIso8601String(),
            'updated_at' => $message->updated_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    public static function toDetailArray(ContactMessage $message): array
    {
        return array_merge(self::toArray($message), [
            'replies' => $message->replies->map(static fn (\App\Models\ContactMessageReply $reply): array => [
                'id' => $reply->id,
                'body' => $reply->body,
                'email_sent' => $reply->email_sent,
                'sender_type' => $reply->sender_type === 'customer' ? 'customer' : 'admin',
                'sender' => $reply->sender_type === 'customer'
                    ? ($reply->sender?->fullName() ?: $message->name)
                    : ($reply->admin?->fullName() ?: 'Администратор'),
                'created_at' => $reply->created_at?->toIso8601String(),
            ])->values()->all(),
        ]);
    }
}
