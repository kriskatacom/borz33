<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Models\SiteSetting;

final class CompanyProfile
{
    /** @return array<string, string|float> */
    public static function get(): array
    {
        $fallback = require dirname(__DIR__, 4) . '/config/company.php';

        try {
            $settings = SiteSetting::query()->first();
            if ($settings === null) return $fallback;

            $mapping = [
                'name' => 'company_name',
                'legal_name' => 'company_legal_name',
                'eik' => 'company_eik',
                'vat' => 'company_vat',
                'mol' => 'company_mol',
                'address' => 'company_address',
                'city' => 'company_city',
                'postal_code' => 'company_postal_code',
                'country' => 'company_country',
                'phone' => 'company_phone',
                'email' => 'company_email',
                'website' => 'company_website',
                'privacy_url' => 'company_privacy_url',
                'terms_url' => 'company_terms_url',
            ];

            foreach ($mapping as $key => $field) {
                $value = trim((string) ($settings->{$field} ?? ''));
                if ($value !== '') $fallback[$key] = $value;
            }
        } catch (\Throwable) {
            // Email delivery should remain available if the settings database is temporarily unavailable.
        }

        return $fallback;
    }
}
