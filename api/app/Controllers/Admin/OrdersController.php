<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Resources\OrderResource;
use App\Services\Accounting\AccountingService;
use App\Services\Orders\OrderAdminService;
use App\Services\Orders\OrderNotificationService;
use Illuminate\Database\Capsule\Manager as Capsule;

class OrdersController extends Controller
{
    public function __construct(
        private readonly OrderAdminService $orders = new OrderAdminService(),
        private readonly AccountingService $accounting = new AccountingService(),
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
        $recordPayment = filter_var($input['record_payment'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $paymentRecorded = false;
        $order = Capsule::connection()->transaction(function () use ($order, $input, $previousStatus, $recordPayment, &$paymentRecorded) {
            $updated = $this->orders->updateFulfillment($order, $input['status'] ?? null, $input['tracking_number'] ?? null);
            $becamePaid = $previousStatus !== 'paid' && (string) $updated->status === 'paid';
            if ($recordPayment && $becamePaid && $updated->payment_method === 'cash_on_delivery') {
                $recordedAmount = (float) $updated->accountingTransactions()->where('type', 'payment')->where('status', 'completed')->sum('amount');
                if ($recordedAmount < (float) $updated->total - 0.009) {
                    $this->accounting->createTransaction(['order_id' => $updated->id, 'type' => 'payment', 'method' => 'cash_on_delivery', 'amount' => (float) $updated->total, 'occurred_at' => date('Y-m-d H:i:s'), 'external_reference' => 'Автоматично при статус „Платена“']);
                    $paymentRecorded = true;
                }
            }
            return $updated;
        });
        $changed = $previousStatus !== (string) $order->status;
        $trackingChanged = $previousTracking !== (string) ($order->tracking_number ?? '');
        $emailSent = $changed ? $this->notifications->sendStatusChanged($order, (string) $order->status) : false;
        $message = $paymentRecorded
            ? 'Статусът е обновен и плащането е записано в Счетоводство.'
            : (!$changed
            ? ($trackingChanged ? 'Данните за проследяване са обновени.' : 'Няма промени за записване.')
            : ($emailSent ? 'Статусът е обновен и клиентът е уведомен.' : 'Статусът е обновен, но имейлът не можа да бъде изпратен.'));

        $this->ok([
            'order' => OrderResource::toArray($order),
            'status_changed' => $changed,
            'tracking_changed' => $trackingChanged,
            'email_sent' => $emailSent,
            'payment_recorded' => $paymentRecorded,
        ], $message);
    }

    private function id(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) $this->error('Поръчката не е намерена.', 404);
        return (int) $id;
    }
}
