<?php

declare(strict_types=1);

namespace App\Services;

class HealthService
{
    public function status(): array
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';

        return [
            'name' => $config['name'] ?? 'API',
            'status' => 'ok',
        ];
    }
}
