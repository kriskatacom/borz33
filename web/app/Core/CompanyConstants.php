<?php

declare(strict_types=1);

namespace Store\Core;

use App\Services\Company\CompanyProfile;

final class CompanyConstants
{
    /** @var array<string, string> */
    private const MAP = [
        'company_name' => 'name',
        'company_legal_name' => 'legal_name',
        'company_eik' => 'eik',
        'company_vat' => 'vat',
        'company_mol' => 'mol',
        'company_address' => 'address',
        'company_city' => 'city',
        'company_postal_code' => 'postal_code',
        'company_country' => 'country',
        'company_phone' => 'phone',
        'company_email' => 'email',
        'company_website' => 'website',
        'company_privacy_url' => 'privacy_url',
        'company_terms_url' => 'terms_url',
    ];

    public static function expand(string $html): string
    {
        $company = CompanyProfile::get();

        return (string) preg_replace_callback('/\{\{\s*(company_[a-z0-9_]+)\s*\}\}/i', static function (array $match) use ($company): string {
            $key = strtolower((string) ($match[1] ?? ''));
            if (!isset(self::MAP[$key])) return (string) ($match[0] ?? '');

            return htmlspecialchars((string) ($company[self::MAP[$key]] ?? ''), ENT_QUOTES, 'UTF-8');
        }, $html);
    }
}
