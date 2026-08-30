<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Resources\MonthlyRevenueReportResource;
use App\Services\Reports\MonthlyRevenueReportService;

class ReportsController extends Controller
{
    public function __construct(private readonly MonthlyRevenueReportService $reports = new MonthlyRevenueReportService()) {}
    public function index(): never { $this->ok(['reports' => $this->reports->list()]); }
    public function store(): never { $report = $this->reports->generate(Request::input('period')); $this->created(['report' => MonthlyRevenueReportResource::toArray($report)], 'Месечният отчет е генериран.'); }
}
