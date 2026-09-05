<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Exceptions\AuthException;
use App\Models\AdminNotification;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SiteSetting;
use Illuminate\Support\Carbon;

final class AdminNotificationService
{
    /** @return array{notifications: list<array<string, mixed>>, unread_count: int, pagination: array{page: int, per_page: int, total: int, last_page: int}} */
    public function list(int $page = 1, int $perPage = 20, bool $archived = false): array
    {
        $query = AdminNotification::query();
        $archived ? $query->whereNotNull('archived_at') : $query->whereNull('archived_at');
        $perPage = max(1, min(100, $perPage));
        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($lastPage, $page));
        $notifications = $query->latest('id')->forPage($page, $perPage)->get();
        return [
            'notifications' => $notifications->map(fn (AdminNotification $item) => $this->toArray($item))->all(),
            'unread_count' => AdminNotification::query()->whereNull('archived_at')->whereNull('read_at')->count(),
            'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => $lastPage],
        ];
    }

    public function markRead(int $id, bool $read): AdminNotification
    {
        $notification = AdminNotification::query()->find($id);
        if ($notification === null) throw new AuthException('Известието не е намерено.', 404);
        $notification->read_at = $read ? Carbon::now() : null;
        $notification->save();
        return $notification;
    }

    public function find(int $id): AdminNotification
    {
        $notification = AdminNotification::query()->find($id);
        if ($notification === null) throw new AuthException('Известието не е намерено.', 404);
        return $notification;
    }

    public function markAllRead(): void
    {
        $now = Carbon::now();
        AdminNotification::query()->whereNull('archived_at')->whereNull('read_at')->update(['read_at' => $now, 'updated_at' => $now]);
    }

    public function archive(int $id): AdminNotification
    {
        $notification = $this->find($id);
        $notification->archived_at = Carbon::now();
        $notification->save();
        return $notification;
    }

    public function unarchive(int $id): AdminNotification
    {
        $notification = $this->find($id);
        $notification->archived_at = null;
        $notification->save();
        return $notification;
    }

    public function delete(int $id): void { $this->find($id)->delete(); }
    public function archiveAll(): void { AdminNotification::query()->whereNull('archived_at')->update(['archived_at' => Carbon::now(), 'updated_at' => Carbon::now()]); }
    public function deleteAll(bool $archived = false): void { $query = AdminNotification::query(); $archived ? $query->whereNotNull('archived_at') : $query->whereNull('archived_at'); $query->delete(); }

    public function lowStock(Product $product, ProductVariant $variant, int $previousStock): void
    {
        $stock = (int) $variant->stock;
        $threshold = (int) (SiteSetting::query()->value('low_stock_threshold') ?? 5);
        if ($threshold <= 0 || $stock >= $previousStock || $stock >= $threshold) return;
        $alreadyOpen = AdminNotification::query()->where('type', 'product.low_stock')->where('subject_id', $variant->id)->whereNull('read_at')->exists();
        if ($alreadyOpen) return;
        $variantName = trim((string) $variant->name);
        $title = $stock === 0 ? 'Продуктът е изчерпан' : 'Ниска наличност на продукт';
        $label = trim((string) $product->name . ($variantName === '' ? '' : ' · ' . $variantName));
        AdminNotification::query()->create(['type' => 'product.low_stock', 'level' => $stock === 0 ? 'critical' : 'warning', 'title' => $title, 'body' => sprintf('%s: наличността е намаляла от %d на %d бр.', $label, $previousStock, $stock), 'link' => '/products/' . $product->id . '/edit?variant=' . $variant->id, 'subject_type' => 'product_variant', 'subject_id' => $variant->id, 'metadata' => ['product_id' => (int) $product->id, 'variant_id' => (int) $variant->id, 'previous_stock' => $previousStock, 'stock' => $stock]]);
    }

    public function stockDepletedAfterPurchase(Product $product, ProductVariant $variant, int $quantity, string $orderNumber): void
    {
        if ((int) $variant->stock !== 0) return;
        if (AdminNotification::query()->where('type', 'product.out_of_stock')->where('subject_id', $variant->id)->whereNull('read_at')->exists()) return;
        $image = $variant->image ?? $product->frontImage;
        $variantName = trim((string) $variant->name);
        $label = trim((string) $product->name . ($variantName === '' ? '' : ' · ' . $variantName));
        AdminNotification::query()->create(['type' => 'product.out_of_stock', 'level' => 'critical', 'title' => 'Продуктът е изчерпан след покупка', 'body' => sprintf('%s е закупен в количество %d бр. по поръчка %s. Наличност: 0 бр.', $label, $quantity, $orderNumber), 'link' => '/products/' . $product->id . '/edit?variant=' . $variant->id, 'subject_type' => 'product_variant', 'subject_id' => $variant->id, 'metadata' => ['product_id' => (int) $product->id, 'variant_id' => (int) $variant->id, 'product_name' => (string) $product->name, 'variant_name' => $variantName, 'quantity' => $quantity, 'stock' => 0, 'image_url' => $image ? '/' . ltrim((string) $image->path, '/') : null, 'order_number' => $orderNumber]]);
    }

    /** @return array<string, mixed> */
    public function toArray(AdminNotification $item): array
    {
        return ['id' => (int) $item->id, 'type' => $item->type, 'level' => $item->level, 'title' => $item->title, 'body' => $item->body, 'link' => $item->link, 'metadata' => $item->metadata ?? [], 'read_at' => $item->read_at?->toIso8601String(), 'archived_at' => $item->archived_at?->toIso8601String(), 'created_at' => $item->created_at?->toIso8601String()];
    }
}
