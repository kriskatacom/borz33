<?php

declare(strict_types=1);

return [
    'name' => getenv('COMPANY_NAME') ?: 'Borz33',
    'legal_name' => getenv('COMPANY_LEGAL_NAME') ?: 'Борз 33 ЕООД',
    'eik' => getenv('COMPANY_EIK') ?: '000000000',
    'vat' => getenv('COMPANY_VAT') ?: '',
    'mol' => getenv('COMPANY_MOL') ?: '',
    'vat_rate' => (float) (getenv('INVOICE_VAT_RATE') ?: 20),
    'address' => getenv('COMPANY_ADDRESS') ?: 'ул. Примерна 1',
    'city' => getenv('COMPANY_CITY') ?: 'София',
    'postal_code' => getenv('COMPANY_POSTAL_CODE') ?: '1000',
    'country' => getenv('COMPANY_COUNTRY') ?: 'България',
    'phone' => getenv('COMPANY_PHONE') ?: '',
    'email' => getenv('COMPANY_EMAIL') ?: 'info@borz33.local',
    'website' => getenv('COMPANY_WEBSITE') ?: 'https://borz33.local',
    'privacy_url' => getenv('COMPANY_PRIVACY_URL') ?: 'https://borz33.local/privacy',
    'terms_url' => getenv('COMPANY_TERMS_URL') ?: 'https://borz33.local/terms',
];
