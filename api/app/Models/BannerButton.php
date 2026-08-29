<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BannerButton extends Model
{
    protected $fillable = [
        'banner_id',
        'label',
        'url',
        'open_in_new_tab',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'banner_id' => 'integer',
            'open_in_new_tab' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function opensInNewTab(): bool
    {
        return $this->open_in_new_tab === true;
    }

    public function banner(): BelongsTo
    {
        return $this->belongsTo(Banner::class);
    }
}
