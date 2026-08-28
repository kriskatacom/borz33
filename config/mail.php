<?php

declare(strict_types=1);

return [
    'dsn' => getenv('MAIL_DSN') ?: 'smtp://mailpit:1025',
    'from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@borz33.local',
    'from_name' => getenv('MAIL_FROM_NAME') ?: 'Borz33',
];
