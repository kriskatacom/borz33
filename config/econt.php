<?php

declare(strict_types=1);

return [
    'environment' => getenv('ECONT_ENVIRONMENT') ?: 'demo',
    'api_base_url' => rtrim((string) (getenv('ECONT_API_BASE_URL') ?: ''), '/'),
    'calculate_path' => ltrim((string) (getenv('ECONT_CALCULATE_PATH') ?: ''), '/'),
    'office_locator_url' => rtrim((string) (getenv('ECONT_OFFICE_LOCATOR_URL') ?: ''), '/'),
    'username' => (string) (getenv('ECONT_USERNAME') ?: ''),
    'password' => (string) (getenv('ECONT_PASSWORD') ?: ''),
    'timeout_seconds' => max(2, min(30, (int) (getenv('ECONT_TIMEOUT_SECONDS') ?: 8))),
    'currency' => (string) (getenv('ECONT_CURRENCY') ?: 'EUR'),
    'sender' => [
        'name' => (string) (getenv('ECONT_SENDER_NAME') ?: ''),
        'agent' => (string) (getenv('ECONT_SENDER_AGENT') ?: ''),
        'phone' => (string) (getenv('ECONT_SENDER_PHONE') ?: ''),
        'office_code' => (string) (getenv('ECONT_SENDER_OFFICE_CODE') ?: ''),
        'city' => (string) (getenv('ECONT_SENDER_CITY') ?: ''),
        'post_code' => (string) (getenv('ECONT_SENDER_POST_CODE') ?: ''),
    ],
];
