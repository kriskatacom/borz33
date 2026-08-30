<?php

declare(strict_types=1);

namespace App\Services\Messages;

use App\Exceptions\ValidationException;
use App\Models\ContactMessage;
use App\Models\ContactMessageAttachment;
use App\Models\ContactMessageReply;
use App\Services\Media\MediaService;
use App\Validation\MediaFileValidator;

class ContactAttachmentService
{
    public const MAX_FILES = 5;

    public function __construct(private readonly MediaService $media = new MediaService(), private readonly MediaFileValidator $validator = new MediaFileValidator()) {}

    /** @param list<array<string, mixed>> $files */
    public function validate(array $files): void
    {
        if (count($files) > self::MAX_FILES) throw new ValidationException(['attachments' => ['Можете да прикачите най-много 5 файла.']]);
        foreach ($files as $file) $this->validator->validateUpload($file);
    }

    /** @param list<array<string, mixed>> $files */
    public function attach(ContactMessage $message, ?ContactMessageReply $reply, array $files): void
    {
        $this->validate($files);
        foreach ($files as $file) {
            $media = $this->media->store($file);
            ContactMessageAttachment::query()->create([
                'contact_message_id' => $message->id,
                'contact_message_reply_id' => $reply?->id,
                'media_file_id' => $media->id,
            ]);
        }
    }
}
