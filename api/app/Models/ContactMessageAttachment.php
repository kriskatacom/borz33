<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessageAttachment extends Model
{
    protected $fillable = ['contact_message_id', 'contact_message_reply_id', 'media_file_id'];
    public function message(): BelongsTo { return $this->belongsTo(ContactMessage::class, 'contact_message_id'); }
    public function reply(): BelongsTo { return $this->belongsTo(ContactMessageReply::class, 'contact_message_reply_id'); }
    public function file(): BelongsTo { return $this->belongsTo(MediaFile::class, 'media_file_id'); }
}
