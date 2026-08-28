<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    public const ROLE_FRONT = 'front';
    public const ROLE_GALLERY = 'gallery';
    public const ROLE_VARIANT = 'variant';

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'media_file_id',
        'role',
        'path',
        'original_name',
        'mime',
        'size',
        'alt',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'sort_order' => 'integer',
            'product_variant_id' => 'integer',
            'media_file_id' => 'integer',
        ];
    }

    public function isFront(): bool
    {
        return $this->role === self::ROLE_FRONT;
    }

    public function isVariant(): bool
    {
        return $this->role === self::ROLE_VARIANT;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
