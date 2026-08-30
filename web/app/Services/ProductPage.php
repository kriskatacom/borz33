<?php

declare(strict_types=1);

namespace Store\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductPersonalizationField;
use App\Models\ProductVariant;
use App\Resources\ProductImageResource;

class ProductPage
{
    public static function findActive(string $slug): ?Product
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'category.parent',
                'parameters',
                'options.values',
                'variants.variantValues.option',
                'variants.variantValues.optionValue',
                'variants.image',
                'personalizationFields',
                'frontImage',
                'galleryImages',
            ])
            ->first();

        return $product instanceof Product ? $product : null;
    }

    /** @return list<Product> */
    public static function related(Product $product, int $limit = 4): array
    {
        if ($product->category_id === null) {
            return [];
        }

        return Product::query()
            ->where('is_active', true)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with('frontImage')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->all();
    }

    public static function money(mixed $value): string
    {
        return number_format((float) $value, 2, ',', ' ') . ' €';
    }

    public static function weight(mixed $grams): string
    {
        $value = max(0, (int) $grams);
        if ($value < 1) return 'Теглото не е посочено';
        if ($value < 1000) return $value . ' г';
        return rtrim(rtrim(number_format($value / 1000, 2, ',', ' '), '0'), ',') . ' кг';
    }

    /** @return list<array{label: string, href: string|null}> */
    public static function crumbs(Product $product): array
    {
        $crumbs = [
            ['label' => 'Каталог', 'href' => '/catalog'],
        ];

        $category = $product->category;

        if ($category?->parent !== null && $category->parent->isActive()) {
            $crumbs[] = [
                'label' => $category->parent->name,
                'href' => '/catalog/' . $category->parent->slug,
            ];
        }

        if ($category !== null && $category->isActive()) {
            $crumbs[] = [
                'label' => $category->name,
                'href' => '/catalog/' . $category->slug,
            ];
        }

        $crumbs[] = ['label' => $product->name, 'href' => null];

        return $crumbs;
    }

    /** @return array<string, mixed> */
    public static function config(Product $product): array
    {
        $gallery = [];

        if ($product->frontImage !== null) {
            $gallery[] = self::image($product->frontImage, $product->name);
        }

        foreach ($product->galleryImages as $image) {
            $gallery[] = self::image($image, $product->name);
        }

        $variants = $product->variants
            ->filter(static fn (ProductVariant $variant): bool => $variant->isActive())
            ->values()
            ->map(static function (ProductVariant $variant) use ($product): array {
                $values = [];

                foreach ($variant->variantValues as $row) {
                    $optionSlug = $row->option?->slug;
                    $valueSlug = $row->optionValue?->slug;

                    if (!is_string($optionSlug) || !is_string($valueSlug) || $optionSlug === '' || $valueSlug === '') {
                        continue;
                    }

                    $values[$optionSlug] = $valueSlug;
                }

                $price = self::variantPrice($product, $variant);
                $compare = self::variantCompare($product, $variant, $price);

                return [
                    'id' => (int) $variant->id,
                    'sku' => $variant->sku !== '' ? (string) $variant->sku : (string) $product->sku,
                    'name' => (string) $variant->name,
                    'price' => $price,
                    'compare_at_price' => $compare,
                    'stock' => (int) $variant->stock,
                    'image' => $variant->image !== null ? self::image($variant->image, $product->name) : null,
                    'values' => $values,
                ];
            })
            ->all();

        if ($variants === []) {
            $price = (string) $product->price;
            $compare = $product->compare_at_price;
            $compareValue = $compare !== null && (float) $compare > (float) $price ? (string) $compare : null;
            $variants[] = [
                'id' => 0,
                'sku' => (string) ($product->sku ?? ''),
                'name' => $product->name,
                'price' => $price,
                'compare_at_price' => $compareValue,
                'stock' => 99,
                'image' => null,
                'values' => [],
            ];
        }

        $default = $product->variants->first(static fn (ProductVariant $variant): bool => $variant->isActive() && $variant->is_default)
            ?? $product->variants->first(static fn (ProductVariant $variant): bool => $variant->isActive() && $variant->isInStock())
            ?? $product->variants->first(static fn (ProductVariant $variant): bool => $variant->isActive());

        $selected = [];

        if ($default instanceof ProductVariant) {
            foreach ($default->variantValues as $row) {
                $optionSlug = $row->option?->slug;
                $valueSlug = $row->optionValue?->slug;

                if (is_string($optionSlug) && is_string($valueSlug) && $optionSlug !== '' && $valueSlug !== '') {
                    $selected[$optionSlug] = $valueSlug;
                }
            }
        }

        return [
            'name' => $product->name,
            'gallery' => $gallery,
            'options' => $product->options->map(static function (ProductOption $option): array {
                return [
                    'slug' => $option->slug,
                    'name' => $option->name,
                    'swatch' => $option->values->contains(static fn (ProductOptionValue $value): bool => is_string($value->hex_color) && $value->hex_color !== ''),
                    'values' => $option->values->map(static fn (ProductOptionValue $value): array => [
                        'slug' => $value->slug,
                        'name' => $value->name,
                        'hex' => $value->hex_color,
                    ])->values()->all(),
                ];
            })->values()->all(),
            'variants' => $variants,
            'selected' => $selected,
            'qty' => 1,
            'fields' => $product->personalizationInputs()->map(static function (ProductPersonalizationField $field): array {
                return [
                    'id' => (int) ($field->id ?? 0),
                    'key' => $field->id !== null ? 'f' . $field->id : 'legacy',
                    'name' => $field->name,
                    'description' => (string) ($field->description ?? ''),
                    'type' => $field->field_type === ProductPersonalizationField::TYPE_TEXTAREA ? 'textarea' : 'text',
                    'required' => $field->isRequired(),
                    'max' => $field->max_length !== null ? (int) $field->max_length : null,
                    'value' => '',
                ];
            })->values()->all(),
        ];
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $options
     * @return array<string, mixed>|null
     */
    public static function postedVariant(array $config, int $variantId, array $options): ?array
    {
        $variants = is_array($config['variants'] ?? null) ? $config['variants'] : [];
        $clean = [];

        foreach ($options as $key => $value) {
            if (is_string($key) && is_string($value) && $key !== '' && $value !== '') {
                $clean[$key] = $value;
            }
        }

        if ($clean !== []) {
            foreach ($variants as $variant) {
                if (!is_array($variant)) {
                    continue;
                }

                $values = is_array($variant['values'] ?? null) ? $variant['values'] : [];

                if (self::sameValues($values, $clean)) {
                    return $variant;
                }
            }

            return null;
        }

        foreach ($variants as $variant) {
            if (is_array($variant) && (int) ($variant['id'] ?? -1) === $variantId) {
                return $variant;
            }
        }

        return null;
    }

    public static function unitPrice(Product $product, ?ProductVariant $variant): string
    {
        if ($variant === null) {
            return (string) $product->price;
        }

        return self::variantPrice($product, $variant);
    }

    public static function defaultVariantId(array $config): int
    {
        $selected = is_array($config['selected'] ?? null) ? $config['selected'] : [];
        $matched = self::postedVariant($config, 0, $selected);

        if ($matched !== null) {
            return (int) $matched['id'];
        }

        $variants = is_array($config['variants'] ?? null) ? $config['variants'] : [];
        $first = $variants[0] ?? null;

        return is_array($first) ? (int) ($first['id'] ?? 0) : 0;
    }

    /** @param array<string, mixed> $left */
    private static function sameValues(array $left, array $right): bool
    {
        if (count($left) !== count($right)) {
            return false;
        }

        foreach ($left as $key => $value) {
            if (($right[$key] ?? null) !== $value) {
                return false;
            }
        }

        return true;
    }

    /** @return array{id: int, url: string, alt: string} */
    private static function image(ProductImage $image, string $fallbackAlt): array
    {
        $data = ProductImageResource::toArray($image);

        return [
            'id' => (int) $image->id,
            'url' => (string) $data['url'],
            'alt' => trim((string) ($image->alt ?? '')) !== '' ? (string) $image->alt : $fallbackAlt,
        ];
    }

    private static function variantPrice(Product $product, ProductVariant $variant): string
    {
        if ($variant->price !== null && (float) $variant->price > 0) {
            return (string) $variant->price;
        }

        return (string) $product->price;
    }

    private static function variantCompare(Product $product, ProductVariant $variant, string $price): ?string
    {
        $compare = $variant->compare_at_price ?? $product->compare_at_price;

        if ($compare === null || (float) $compare <= (float) $price) {
            return null;
        }

        return (string) $compare;
    }
}
