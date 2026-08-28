<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\HealthService;

class HealthController extends Controller
{
    public function __construct(
        private readonly HealthService $healthService = new HealthService()
    ) {
    }

    public function index(): never
    {
        $this->ok($this->healthService->status(), 'API is running');
    }
}
