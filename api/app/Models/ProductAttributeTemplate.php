<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttributeTemplate extends Model
{
    protected $fillable = ['name', 'category_id', 'parameters', 'options'];

    protected function casts(): array
    {
        return ['category_id' => 'integer', 'parameters' => 'array', 'options' => 'array'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
