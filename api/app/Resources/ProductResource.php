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

class ProductResource
{
    /** @return array<string, mixed> */
    public static function toAdminArray(Product $product): array
    {
        $product->loadMissing([
            'parameters',
            'options.values',
            'variants.variantValues.option',
            'variants.variantValues.optionValue',
            'personalizationFields',
        ]);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'price' => $product->price,
            'compare_at_price' => $product->compare_at_price,
            'is_active' => $product->is_active,
            'personalization_enabled' => $product->personalization_enabled,
            'personalization_label' => $product->personalization_label,
            'personalization_description' => $product->personalization_description,
            'personalization_required' => $product->personalization_required,
            'personalization_max_length' => $product->personalization_max_length,
            'sort_order' => $product->sort_order,
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
                'option_values' => $variant->variantValues->map(static fn (ProductVariantValue $row): array => [
                    'option' => $row->option?->slug,
                    'option_name' => $row->option?->name,
                    'value' => $row->optionValue?->slug,
                    'value_name' => $row->optionValue?->name,
                    'hex_color' => $row->optionValue?->hex_color,
                ])->values()->all(),
            ])->values()->all(),
            'personalization_fields' => $product->personalizationFields->map(
                static fn (ProductPersonalizationField $field): array => [
                    'id' => $field->id,
                    'name' => $field->name,
                    'description' => $field->description,
                    'field_type' => $field->field_type,
                    'is_required' => $field->is_required,
                    'max_length' => $field->max_length,
                    'sort_order' => $field->sort_order,
                ]
            )->values()->all(),
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
            'sku' => $product->sku,
            'short_description' => $product->short_description,
            'price' => $product->price,
            'compare_at_price' => $product->compare_at_price,
            'is_active' => $product->is_active,
            'personalization_enabled' => $product->personalization_enabled,
            'sort_order' => $product->sort_order,
            'variants_count' => (int) ($product->variants_count ?? $product->variants()->count()),
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
}
