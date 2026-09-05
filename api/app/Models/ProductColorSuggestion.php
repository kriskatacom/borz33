<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductColorSuggestion extends Model
{
    protected $fillable = [
        'product_id', 'product_variant_id', 'product_image_id', 'color_name_bg',
        'color_hex', 'confidence', 'is_multicolor', 'model',
    ];

    protected function casts(): array
    {
        return ['confidence' => 'float', 'is_multicolor' => 'boolean'];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
