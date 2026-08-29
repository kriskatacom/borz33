<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Services\DashboardAdminService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardAdminService $dashboard = new DashboardAdminService()
    ) {
    }

    public function show(): never
    {
        $this->ok($this->dashboard->summary(), 'Обобщение за таблото.');
    }
}
