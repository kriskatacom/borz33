<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPersonalizationField extends Model
{
    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';

    protected $fillable = [
        'product_id',
        'name',
        'description',
        'field_type',
        'is_required',
        'max_length',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'max_length' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function isRequired(): bool
    {
        return $this->is_required === true;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
