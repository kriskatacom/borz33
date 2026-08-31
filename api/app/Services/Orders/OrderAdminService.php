<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Exceptions\AuthException;
use App\Exceptions\ValidationException;
use App\Models\Order;
use App\Resources\OrderResource;
use Illuminate\Database\Eloquent\Builder;

class OrderAdminService
{
    public const STATUSES = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 20)));
        $query = Order::query()->withCount('items');
        $search = trim((string) ($filters['q'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        if ($search !== '') {
            $query->where(static function (Builder $builder) use ($search): void {
                $like = '%' . $search . '%';
                $builder->where('number', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        if ($status !== '' && $status !== 'all' && in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        }

        foreach (['delivery_method', 'payment_method'] as $field) {
            $value = trim((string) ($filters[$field] ?? ''));
            if ($value !== '' && $value !== 'all') $query->where($field, $value);
        }

        $total = (clone $query)->count();
        $orders = $query->orderByDesc('created_at')->orderByDesc('id')->forPage($page, $perPage)->get();

        return [
            'orders' => $orders->map(static fn (Order $order): array => OrderResource::toListArray($order))->all(),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
        ];
    }

    public function find(int $id): Order
    {
        $order = Order::query()->with(['items.product.frontImage', 'invoices'])->withCount('items')->find($id);
        if ($order === null) throw new AuthException('Поръчката не е намерена.', 404);
        return $order;
    }

    public function updateFulfillment(Order $order, mixed $status, mixed $trackingNumber): Order
    {
        $status = is_string($status) ? trim($status) : '';
        if (!in_array($status, self::STATUSES, true)) {
            throw new ValidationException(['status' => ['Изберете валиден статус на поръчката.']]);
        }

        $trackingNumber = is_string($trackingNumber) ? strtoupper(trim($trackingNumber)) : '';
        if ($trackingNumber !== '' && preg_match('/^[A-Z0-9-]{6,64}$/', $trackingNumber) !== 1) {
            throw new ValidationException(['tracking_number' => ['Номерът трябва да съдържа между 6 и 64 цифри, букви или тирета.']]);
        }
        $order->status = $status;
        $order->tracking_number = $trackingNumber !== '' ? $trackingNumber : null;
        if ($order->shipped_at === null && in_array($status, ['shipped', 'delivered'], true)) $order->shipped_at = new \DateTimeImmutable();
        $order->save();
        return $this->find((int) $order->id);
    }
}
