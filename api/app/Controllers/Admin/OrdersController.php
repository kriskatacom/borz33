<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Resources\OrderResource;
use App\Services\Orders\OrderAdminService;
use App\Services\Orders\OrderNotificationService;

class OrdersController extends Controller
{
    public function __construct(
        private readonly OrderAdminService $orders = new OrderAdminService(),
        private readonly OrderNotificationService $notifications = new OrderNotificationService()
    ) {}

    public function index(): never
    {
        $this->ok($this->orders->paginate(Request::query()), 'Списък с поръчки.');
    }

    public function show(string $id): never
    {
        $this->ok(['order' => OrderResource::toArray($this->orders->find($this->id($id)))]);
    }

    public function update(string $id): never
    {
        $order = $this->orders->find($this->id($id));
        $previousStatus = (string) $order->status;
        $previousTracking = (string) ($order->tracking_number ?? '');
        $input = Request::input();
        $order = $this->orders->updateFulfillment($order, $input['status'] ?? null, $input['tracking_number'] ?? null);
        $changed = $previousStatus !== (string) $order->status;
        $trackingChanged = $previousTracking !== (string) ($order->tracking_number ?? '');
        $emailSent = $changed ? $this->notifications->sendStatusChanged($order, (string) $order->status) : false;
        $message = !$changed
            ? ($trackingChanged ? 'Данните за проследяване са обновени.' : 'Няма промени за записване.')
            : ($emailSent ? 'Статусът е обновен и клиентът е уведомен.' : 'Статусът е обновен, но имейлът не можа да бъде изпратен.');

        $this->ok([
            'order' => OrderResource::toArray($order),
            'status_changed' => $changed,
            'tracking_changed' => $trackingChanged,
            'email_sent' => $emailSent,
        ], $message);
    }

    private function id(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) $this->error('Поръчката не е намерена.', 404);
        return (int) $id;
    }
}
