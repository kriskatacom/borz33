<?php

declare(strict_types=1);

namespace App\Models;

class AdminNotification extends Model
{
    protected $fillable = ['type', 'level', 'title', 'body', 'link', 'subject_type', 'subject_id', 'metadata', 'read_at', 'archived_at'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'read_at' => 'datetime', 'archived_at' => 'datetime'];
    }
}
