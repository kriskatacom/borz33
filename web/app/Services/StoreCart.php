<?php

declare(strict_types=1);

namespace Store\Services;

use App\Models\Product;
use App\Models\ProductPersonalizationField;
use App\Models\ProductVariant;
use App\Resources\ProductImageResource;

class StoreCart
{
    public const MAX_QTY = 99;

    /**
     * @param array<string, mixed> $personalization
     */
    public static function add(int $productId, int $variantId, int $qty, array $personalization): void
    {
        $qty = max(1, min(self::MAX_QTY, $qty));
        $items = self::items();
        $key = self::lineKey($productId, $variantId, $personalization);

        if (isset($items[$key])) {
            $items[$key]['qty'] = min(self::MAX_QTY, (int) $items[$key]['qty'] + $qty);
        } else {
            $items[$key] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'qty' => $qty,
                'personalization' => $personalization,
            ];
        }

        self::save($items);
    }

    public static function count(): int
    {
        $total = 0;

        foreach (self::items() as $item) {
            $total += $item['qty'];
        }

        return $total;
    }

    public static function clear(): void
    {
        unset($_SESSION['store_cart']);
    }

    public static function updateQty(int $index, int $qty): void
    {
        $items = array_values(self::items());

        if (!isset($items[$index])) {
            return;
        }

        if ($qty < 1) {
            unset($items[$index]);
            self::save($items);

            return;
        }

        $items[$index]['qty'] = min(self::MAX_QTY, $qty);
        self::save($items);
    }

    public static function remove(int $index): void
    {
        $items = array_values(self::items());
        unset($items[$index]);
        self::save($items);
    }

    /**
     * @return list<array{
     *     index: int,
     *     product_id: int,
     *     variant_id: int,
     *     qty: int,
     *     name: string,
     *     href: string,
     *     sku: string,
     *     options: string,
     *     notes: list<string>,
     *     image: ?string,
     *     alt: string,
     *     price: string,
     *     total: string
     * }>
     */
    public static function lines(): array
    {
        $rows = array_values(self::items());

        if ($rows === []) {
            return [];
        }

        $ids = array_values(array_unique(array_map(static fn (array $row): int => $row['product_id'], $rows)));
        $products = Product::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->with([
                'frontImage',
                'personalizationFields',
                'variants.image',
                'variants.variantValues.option',
                'variants.variantValues.optionValue',
            ])
            ->get()
            ->keyBy('id');

        $kept = [];
        $lines = [];
        $changed = false;

        foreach ($rows as $row) {
            $product = $products->get($row['product_id']);

            if (!$product instanceof Product) {
                $changed = true;
                continue;
            }

            $variant = self::variantFor($product, $row['variant_id']);

            if ($row['variant_id'] > 0 && $variant === null) {
                $changed = true;
                continue;
            }

            if ($variant !== null && !$variant->isInStock()) {
                $changed = true;
                continue;
            }

            $price = ProductPage::unitPrice($product, $variant);
            $qty = min(self::MAX_QTY, max(1, $row['qty']));

            if ($variant !== null) {
                $qty = min($qty, (int) $variant->stock);
            }

            if ($qty !== (int) $row['qty']) {
                $changed = true;
            }

            $image = $variant?->image ?? $product->frontImage;
            $optionLabels = [];

            if ($variant !== null) {
                foreach ($variant->variantValues as $value) {
                    $optionName = $value->option?->name;
                    $valueName = $value->optionValue?->name;

                    if (is_string($optionName) && is_string($valueName) && $optionName !== '' && $valueName !== '') {
                        $optionLabels[] = $optionName . ': ' . $valueName;
                    }
                }
            }

            $row['qty'] = $qty;
            $kept[] = $row;
            $lines[] = [
                'index' => count($lines),
                'product_id' => (int) $product->id,
                'variant_id' => $row['variant_id'],
                'qty' => $qty,
                'stock' => $variant !== null ? (int) $variant->stock : self::MAX_QTY,
                'name' => (string) $product->name,
                'href' => '/products/' . $product->slug,
                'sku' => $variant !== null && $variant->sku !== '' ? (string) $variant->sku : (string) ($product->sku ?? ''),
                'options' => implode(', ', $optionLabels),
                'notes' => self::notes($product, $row['personalization']),
                'image' => $image !== null ? (string) ProductImageResource::toArray($image)['url'] : null,
                'alt' => $image !== null && trim((string) $image->alt) !== '' ? (string) $image->alt : (string) $product->name,
                'price' => $price,
                'total' => (string) ((float) $price * $qty),
                'weight_grams' => (int) $product->weight_grams,
                'weight' => ProductPage::weight((int) $product->weight_grams),
                'total_weight_grams' => (int) $product->weight_grams * $qty,
                'total_weight' => ProductPage::weight((int) $product->weight_grams * $qty),
            ];
        }

        if ($changed || count($kept) !== count($rows)) {
            self::save($kept);
        }

        return $lines;
    }

    public static function moneyTotal(array $lines): string
    {
        $sum = 0.0;

        foreach ($lines as $line) {
            $sum += (float) ($line['total'] ?? 0);
        }

        return ProductPage::money($sum);
    }

    /** @param list<array<string, mixed>> $lines */
    public static function weightTotal(array $lines): string
    {
        foreach ($lines as $line) {
            if ((int) ($line['weight_grams'] ?? 0) < 1) return 'Не е изчислено';
        }
        return ProductPage::weight(array_sum(array_map(static fn (array $line): int => (int) ($line['total_weight_grams'] ?? 0), $lines)));
    }

    /** @return list<array{product_id: int, variant_id: int, qty: int, personalization: array<string, mixed>}> */
    public static function items(): array
    {
        $raw = $_SESSION['store_cart'] ?? [];

        if (!is_array($raw)) {
            return [];
        }

        $items = [];

        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }

            $productId = (int) ($row['product_id'] ?? 0);
            $variantId = (int) ($row['variant_id'] ?? 0);
            $qty = (int) ($row['qty'] ?? 0);

            if ($productId < 1 || $variantId < 0 || $qty < 1) {
                continue;
            }

            $personalization = $row['personalization'] ?? [];
            $items[self::lineKey($productId, $variantId, is_array($personalization) ? $personalization : [])] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'qty' => min(self::MAX_QTY, $qty),
                'personalization' => is_array($personalization) ? $personalization : [],
            ];
        }

        return $items;
    }

    /** @param array<int, array{product_id: int, variant_id: int, qty: int, personalization: array<string, mixed>}> $items */
    private static function save(array $items): void
    {
        $_SESSION['store_cart'] = array_values($items);
    }

    private static function variantFor(Product $product, int $variantId): ?ProductVariant
    {
        if ($variantId < 1) {
            return null;
        }

        $variant = $product->variants->first(
            static fn (ProductVariant $item): bool => (int) $item->id === $variantId && $item->isActive()
        );

        return $variant instanceof ProductVariant ? $variant : null;
    }

    /**
     * @param array<string, mixed> $personalization
     * @return list<string>
     */
    private static function notes(Product $product, array $personalization): array
    {
        $notes = [];

        foreach ($product->personalizationInputs() as $field) {
            $key = $field->id !== null ? 'f' . $field->id : 'legacy';
            $text = $personalization[$key] ?? '';
            $text = is_string($text) ? trim($text) : '';

            if ($text === '') {
                continue;
            }

            $label = $field instanceof ProductPersonalizationField ? (string) $field->name : 'Персонализация';
            $notes[] = $label . ': ' . $text;
        }

        return $notes;
    }

    /** @param array<string, mixed> $personalization */
    private static function lineKey(int $productId, int $variantId, array $personalization): string
    {
        ksort($personalization);

        return $productId . ':' . $variantId . ':' . md5((string) json_encode($personalization, JSON_UNESCAPED_UNICODE));
    }
}
