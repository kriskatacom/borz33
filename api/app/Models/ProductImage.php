<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    public const ROLE_FRONT = 'front';
    public const ROLE_GALLERY = 'gallery';

    protected $fillable = [
        'product_id',
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
        ];
    }

    public function isFront(): bool
    {
        return $this->role === self::ROLE_FRONT;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
