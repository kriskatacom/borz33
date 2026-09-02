<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banner extends Model
{
    use SoftDeletes;

    public const LAYOUT_SPLIT = 'split';
    public const LAYOUT_OVERLAY = 'overlay';
    public const LAYOUT_STACK = 'stack';

    /** @var list<string> */
    public const LAYOUTS = [
        self::LAYOUT_SPLIT,
        self::LAYOUT_OVERLAY,
        self::LAYOUT_STACK,
    ];

    /** @var array<string, string> */
    public const IMAGE_POSITIONS = [
        'top-left' => 'left top',
        'top' => 'center top',
        'top-right' => 'right top',
        'left' => 'left center',
        'center' => 'center center',
        'right' => 'right center',
        'bottom-left' => 'left bottom',
        'bottom' => 'center bottom',
        'bottom-right' => 'right bottom',
    ];

    /** @var array<string, array{horizontal: string, vertical: string}> */
    public const CONTENT_POSITIONS = [
        'top-left' => ['horizontal' => 'flex-start', 'vertical' => 'flex-start'],
        'top' => ['horizontal' => 'center', 'vertical' => 'flex-start'],
        'top-right' => ['horizontal' => 'flex-end', 'vertical' => 'flex-start'],
        'left' => ['horizontal' => 'flex-start', 'vertical' => 'center'],
        'center' => ['horizontal' => 'center', 'vertical' => 'center'],
        'right' => ['horizontal' => 'flex-end', 'vertical' => 'center'],
        'bottom-left' => ['horizontal' => 'flex-start', 'vertical' => 'flex-end'],
        'bottom' => ['horizontal' => 'center', 'vertical' => 'flex-end'],
        'bottom-right' => ['horizontal' => 'flex-end', 'vertical' => 'flex-end'],
    ];

    protected $fillable = [
        'title',
        'slug',
        'text',
        'layout',
        'height',
        'width_mode',
        'image_position',
        'content_position',
        'media_file_id',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'media_file_id' => 'integer',
            'height' => 'integer',
            'width_mode' => 'string',
            'image_position' => 'string',
            'content_position' => 'string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected function slug(): Attribute
    {
        return Attribute::make(
            set: static fn (string $value): string => strtolower(trim($value)),
        );
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function layoutKey(): string
    {
        $value = (string) $this->layout;

        return in_array($value, self::LAYOUTS, true) ? $value : self::LAYOUT_SPLIT;
    }

    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class);
    }

    public function buttons(): HasMany
    {
        return $this->hasMany(BannerButton::class)->orderBy('sort_order')->orderBy('id');
    }
}
