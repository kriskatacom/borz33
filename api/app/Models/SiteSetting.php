<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSetting extends Model
{
    protected $fillable = ['logo_media_file_id', 'product_personalization_default', 'admin_background', 'admin_background_overlay', 'vat_enabled', 'free_shipping_threshold', 'econt_operations_enabled', 'econt_environment', 'econt_production_username', 'econt_production_password', 'econt_production_verified_at'];

    protected function casts(): array
    {
        return ['logo_media_file_id' => 'integer', 'product_personalization_default' => 'array', 'vat_enabled' => 'boolean', 'free_shipping_threshold' => 'decimal:2', 'econt_operations_enabled' => 'boolean', 'econt_production_verified_at' => 'datetime'];
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'logo_media_file_id');
    }
}
