<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductParameter;
use App\Models\ProductPersonalizationField;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use App\Models\SiteSetting;

class ProductResource
{
    /** @return array<string, mixed> */
    public static function toAdminArray(Product $product): array
    {
        $product->loadMissing([
            'category',
            'parameters',
            'options.values',
            'variants.variantValues.option',
            'variants.variantValues.optionValue',
            'variants.image',
            'personalizationFields',
            'frontImage',
            'galleryImages',
        ]);
        $personalization = $product->effectivePersonalization();
        $defaultPersonalization = SiteSetting::query()->first()?->product_personalization_default;
        $personalizationFields = $product->personalization_override
            ? $product->personalizationFields->map(static fn (ProductPersonalizationField $field): array => [
                'id' => $field->id,
                'name' => $field->name,
                'description' => $field->description,
                'field_type' => $field->field_type,
                'is_required' => $field->is_required,
                'max_length' => $field->max_length,
                'sort_order' => $field->sort_order,
            ])->values()->all()
            : array_values(is_array($personalization['fields'] ?? null) ? $personalization['fields'] : []);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'category_id' => $product->category_id,
            'category' => self::categorySummary($product),
            'sku' => $product->sku,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'price' => $product->price,
            'compare_at_price' => $product->compare_at_price,
            'weight_grams' => (int) $product->weight_grams,
            'is_active' => $product->is_active,
            'personalization_enabled' => (bool) ($personalization['enabled'] ?? false),
            'personalization_label' => $personalization['label'] ?? null,
            'personalization_description' => $personalization['description'] ?? null,
            'personalization_required' => (bool) ($personalization['required'] ?? false),
            'personalization_max_length' => (int) ($personalization['max_length'] ?? 80),
            'personalization_override' => (bool) $product->personalization_override,
            'personalization_default' => $defaultPersonalization,
            'sort_order' => $product->sort_order,
            'front_image' => $product->frontImage
                ? ProductImageResource::toArray($product->frontImage)
                : null,
            'gallery_images' => ProductImageResource::collection($product->galleryImages),
            'parameters' => $product->parameters->map(static fn (ProductParameter $row): array => [
                'id' => $row->id,
                'name' => $row->name,
                'value' => $row->value,
                'sort_order' => $row->sort_order,
            ])->values()->all(),
            'options' => $product->options->map(static fn (ProductOption $option): array => [
                'id' => $option->id,
                'name' => $option->name,
                'slug' => $option->slug,
                'sort_order' => $option->sort_order,
                'values' => $option->values->map(static fn (ProductOptionValue $value): array => [
                    'id' => $value->id,
                    'name' => $value->name,
                    'slug' => $value->slug,
                    'hex_color' => $value->hex_color,
                    'sort_order' => $value->sort_order,
                ])->values()->all(),
            ])->values()->all(),
            'variants' => $product->variants->map(static fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'name' => $variant->name,
                'price' => $variant->price,
                'compare_at_price' => $variant->compare_at_price,
                'stock' => $variant->stock,
                'is_default' => $variant->is_default,
                'is_active' => $variant->is_active,
                'sort_order' => $variant->sort_order,
                'image' => $variant->image ? ProductImageResource::toArray($variant->image) : null,
                'option_values' => $variant->variantValues->map(static fn (ProductVariantValue $row): array => [
                    'option' => $row->option?->slug,
                    'option_name' => $row->option?->name,
                    'value' => $row->optionValue?->slug,
                    'value_name' => $row->optionValue?->name,
                    'hex_color' => $row->optionValue?->hex_color,
                ])->values()->all(),
            ])->values()->all(),
            'personalization_fields' => $personalizationFields,
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
            'deleted_at' => $product->deleted_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    public static function toAdminListArray(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'category_id' => $product->category_id,
            'category' => self::categorySummary($product),
            'sku' => $product->sku,
            'short_description' => $product->short_description,
            'price' => $product->price,
            'compare_at_price' => $product->compare_at_price,
            'weight_grams' => (int) $product->weight_grams,
            'is_active' => $product->is_active,
            'personalization_enabled' => $product->personalization_enabled,
            'sort_order' => $product->sort_order,
            'variants_count' => (int) ($product->variants_count ?? $product->variants()->count()),
            'front_image' => $product->frontImage
                ? ProductImageResource::toArray($product->frontImage)
                : null,
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
            'deleted_at' => $product->deleted_at?->toIso8601String(),
        ];
    }

    /** @param iterable<int, Product> $products */
    public static function collection(iterable $products): array
    {
        $items = [];

        foreach ($products as $product) {
            $items[] = self::toAdminListArray($product);
        }

        return $items;
    }

    /** @return array{id: int, name: string, slug: string}|null */
    private static function categorySummary(Product $product): ?array
    {
        $product->loadMissing('category');

        if ($product->category === null) {
            return null;
        }

        return [
            'id' => $product->category->id,
            'name' => $product->category->name,
            'slug' => $product->category->slug,
        ];
    }
}
