<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class PageTemplate extends Model
{
    protected $fillable = ['name', 'slug', 'view', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }
}
