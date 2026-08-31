<?php

declare(strict_types=1);

return [
    'default_environment' => getenv('ECONT_ENVIRONMENT') ?: 'demo',
    'calculate_path' => ltrim((string) (getenv('ECONT_CALCULATE_PATH') ?: ''), '/'),
    'connection_test_path' => ltrim((string) (getenv('ECONT_CONNECTION_TEST_PATH') ?: 'Profile/ProfileService.getClientProfiles.json'), '/'),
    'timeout_seconds' => max(2, min(30, (int) (getenv('ECONT_TIMEOUT_SECONDS') ?: 8))),
    'currency' => (string) (getenv('ECONT_CURRENCY') ?: 'EUR'),
    'environments' => [
        'demo' => [
            'api_base_url' => rtrim((string) (getenv('ECONT_DEMO_API_BASE_URL') ?: getenv('ECONT_API_BASE_URL') ?: ''), '/'),
            'office_locator_url' => rtrim((string) (getenv('ECONT_DEMO_OFFICE_LOCATOR_URL') ?: getenv('ECONT_OFFICE_LOCATOR_URL') ?: ''), '/'),
            'username' => (string) (getenv('ECONT_DEMO_USERNAME') ?: getenv('ECONT_USERNAME') ?: ''),
            'password' => (string) (getenv('ECONT_DEMO_PASSWORD') ?: getenv('ECONT_PASSWORD') ?: ''),
        ],
        'production' => [
            'api_base_url' => rtrim((string) (getenv('ECONT_PRODUCTION_API_BASE_URL') ?: ''), '/'),
            'office_locator_url' => rtrim((string) (getenv('ECONT_PRODUCTION_OFFICE_LOCATOR_URL') ?: ''), '/'),
        ],
    ],
    'tracking_url' => rtrim((string) (getenv('ECONT_TRACKING_URL') ?: 'https://ee.econt.com/load_direct.php'), '/'),
    'sender' => [
        'name' => (string) (getenv('ECONT_SENDER_NAME') ?: ''),
        'agent' => (string) (getenv('ECONT_SENDER_AGENT') ?: ''),
        'phone' => (string) (getenv('ECONT_SENDER_PHONE') ?: ''),
        'office_code' => (string) (getenv('ECONT_SENDER_OFFICE_CODE') ?: ''),
        'city' => (string) (getenv('ECONT_SENDER_CITY') ?: ''),
        'post_code' => (string) (getenv('ECONT_SENDER_POST_CODE') ?: ''),
    ],
];
