<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageField extends Model
{
    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_FILE = 'file';

    protected $fillable = [
        'page_id',
        'name',
        'slug',
        'field_type',
        'value',
        'media_file_id',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'page_id' => 'integer',
            'media_file_id' => 'integer',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function isRequired(): bool
    {
        return $this->is_required === true;
    }

    public function isFile(): bool
    {
        return $this->field_type === self::TYPE_FILE;
    }

    public function isText(): bool
    {
        return in_array($this->field_type, [self::TYPE_TEXT, self::TYPE_TEXTAREA], true);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class);
    }
}
