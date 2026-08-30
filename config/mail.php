<?php

declare(strict_types=1);

return [
    'dsn' => getenv('MAIL_DSN') ?: 'smtp://mailpit:1025',
    'from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@borz33.local',
    'from_name' => getenv('MAIL_FROM_NAME') ?: 'Borz33',
    'order_admin_address' => getenv('ORDER_ADMIN_EMAIL') ?: getenv('COMPANY_EMAIL') ?: 'info@borz33.local',
    'verification_ttl_minutes' => (int) (getenv('MAIL_VERIFICATION_TTL_MINUTES') ?: 15),
];
