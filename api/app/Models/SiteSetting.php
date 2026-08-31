<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSetting extends Model
{
    protected $fillable = ['logo_media_file_id', 'product_personalization_default', 'vat_enabled'];

    protected function casts(): array
    {
        return ['logo_media_file_id' => 'integer', 'product_personalization_default' => 'array', 'vat_enabled' => 'boolean'];
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'logo_media_file_id');
    }
}
