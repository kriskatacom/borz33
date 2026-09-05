<?php

declare(strict_types=1);

return [
    'max_attempts' => (int) (getenv('AUTH_MAX_ATTEMPTS') ?: 5),
    'lockout_minutes' => (int) (getenv('AUTH_LOCKOUT_MINUTES') ?: 15),
    'ip_max_attempts' => (int) (getenv('AUTH_IP_MAX_ATTEMPTS') ?: 25),
    'device_code_ttl_minutes' => (int) (getenv('AUTH_DEVICE_CODE_TTL_MINUTES') ?: 15),
    'device_code_max_attempts' => (int) (getenv('AUTH_DEVICE_CODE_MAX_ATTEMPTS') ?: 5),
    'token_ttl_days' => (int) (getenv('AUTH_TOKEN_TTL_DAYS') ?: 30),
    'password_reset_ttl_minutes' => (int) (getenv('AUTH_PASSWORD_RESET_TTL_MINUTES') ?: 60),
    'admin_public_url' => getenv('ADMIN_PUBLIC_URL') ?: 'http://localhost:3000',
    'public_url' => getenv('WEB_PUBLIC_URL') ?: 'http://localhost:3000',
];
