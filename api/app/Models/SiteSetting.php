<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSetting extends Model
{
    protected $fillable = ['logo_media_file_id', 'product_personalization_default', 'admin_background', 'admin_background_overlay', 'storefront_status', 'storefront_indexing_enabled', 'company_name', 'company_legal_name', 'company_eik', 'company_vat', 'company_mol', 'company_address', 'company_city', 'company_postal_code', 'company_country', 'company_phone', 'company_email', 'company_website', 'company_privacy_url', 'company_terms_url', 'vat_enabled', 'free_shipping_threshold', 'low_stock_threshold', 'econt_operations_enabled', 'econt_environment', 'econt_production_username', 'econt_production_password', 'econt_production_verified_at'];

    protected function casts(): array
    {
        return ['logo_media_file_id' => 'integer', 'product_personalization_default' => 'array', 'storefront_indexing_enabled' => 'boolean', 'vat_enabled' => 'boolean', 'free_shipping_threshold' => 'decimal:2', 'low_stock_threshold' => 'integer', 'econt_operations_enabled' => 'boolean', 'econt_production_verified_at' => 'datetime'];
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'logo_media_file_id');
    }
}
