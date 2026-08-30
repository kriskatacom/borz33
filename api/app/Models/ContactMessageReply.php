<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessageReply extends Model
{
    protected $fillable = ['contact_message_id', 'admin_user_id', 'sender_type', 'sender_user_id', 'body', 'email_sent'];
    protected function casts(): array { return ['email_sent' => 'boolean']; }
    public function message(): BelongsTo { return $this->belongsTo(ContactMessage::class, 'contact_message_id'); }
    public function admin(): BelongsTo { return $this->belongsTo(User::class, 'admin_user_id'); }
    public function sender(): BelongsTo { return $this->belongsTo(User::class, 'sender_user_id'); }
}
