<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaFile extends Model
{
    public const KIND_IMAGE = 'image';
    public const KIND_VIDEO = 'video';
    public const KIND_AUDIO = 'audio';
    public const KIND_DOCUMENT = 'document';
    public const KIND_OTHER = 'other';

    protected $fillable = [
        'path',
        'original_name',
        'mime',
        'extension',
        'kind',
        'size',
        'original_size',
        'alt',
        'title',
        'width',
        'height',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'original_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'uploaded_by' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function pageFields(): HasMany
    {
        return $this->hasMany(PageField::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class);
    }

    public function isImage(): bool
    {
        return $this->kind === self::KIND_IMAGE;
    }
}
