<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactMessage extends Model
{
    protected $fillable = ['user_id', 'name', 'email', 'phone', 'subject', 'message', 'ip_hash', 'email_sent', 'read_at'];

    protected function casts(): array
    {
        return ['email_sent' => 'boolean', 'read_at' => 'datetime'];
    }

    public function replies(): HasMany { return $this->hasMany(ContactMessageReply::class)->orderBy('created_at')->orderBy('id'); }
}
