<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Exceptions\AuthException;
use App\Exceptions\ValidationException;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductParameter;
use App\Models\ProductPersonalizationField;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use App\Resources\ProductResource;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductAdminService
{
    /** @param array<string, mixed> $filters */
    public function paginate(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 20)));
        $query = $this->filteredQuery($filters);
        $total = (clone $query)->count();
        $products = $query
            ->with('frontImage')
            ->withCount('variants')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get();

        return [
            'products' => ProductResource::collection($products),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
        ];
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Product
    {
        return Capsule::connection()->transaction(function () use ($data): Product {
            $product = new Product();
            $product->forceFill($this->productAttributes($data, null, null))->save();
            $this->syncNested($product, $data);

            return $this->fresh($product);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Product $product, array $data): Product
    {
        return Capsule::connection()->transaction(function () use ($product, $data): Product {
            $attributes = $this->productAttributes($data, (int) $product->id, $product);

            if ($attributes !== []) {
                $product->forceFill($attributes)->save();
            }

            $this->syncNested($product, $data);

            return $this->fresh($product);
        });
    }

    public function find(int $id, bool $withTrashed = false): Product
    {
        $query = $withTrashed ? Product::withTrashed() : Product::query();
        $product = $query->find($id);

        if ($product === null) {
            throw new AuthException('Продуктът не е намерен.', 404);
        }

        return $product;
    }

    public function delete(Product $product, bool $purgeImages = false): void
    {
        if ($product->trashed()) {
            throw new AuthException('Продуктът вече е изтрит.');
        }

        if ($purgeImages) {
            (new ProductImageService())->purgeForProduct($product);
        }

        $product->delete();
    }

    public function restore(int $id): Product
    {
        $product = $this->find($id, true);

        if ($product->deleted_at === null) {
            throw new AuthException('Продуктът не е изтрит.');
        }

        $product->restore();

        return $this->fresh($product);
    }

    /** @param array<string, mixed> $filters */
    private function filteredQuery(array $filters): Builder
    {
        $status = (string) ($filters['status'] ?? 'all');
        $query = match ($status) {
            'deleted' => Product::onlyTrashed(),
            'inactive' => Product::query()->where('is_active', false),
            'active' => Product::query()->where('is_active', true),
            default => Product::query(),
        };

        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(static function (Builder $builder) use ($like): void {
                $builder
                    ->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('short_description', 'like', $like);
            });
        }

        return $query;
    }

    /** @param array<string, mixed> $data */
    private function productAttributes(array $data, ?int $productId, ?Product $existing): array
    {
        if ($existing === null) {
            return $this->fullProductAttributes($data, $productId);
        }

        $attributes = [];

        if (array_key_exists('name', $data)) {
            $attributes['name'] = $data['name'];
        }

        if (array_key_exists('slug', $data) || array_key_exists('name', $data)) {
            $slug = trim((string) ($data['slug'] ?? $existing->slug ?? ''));

            if ($slug === '') {
                $slug = $this->uniqueSlug((string) ($data['name'] ?? $existing->name), $productId);
            }

            $attributes['slug'] = $slug;
        }

        if (array_key_exists('sku', $data)) {
            $sku = trim((string) ($data['sku'] ?? ''));
            $attributes['sku'] = $sku === '' ? null : $sku;
        }

        foreach (['short_description', 'description', 'compare_at_price', 'personalization_label', 'personalization_description'] as $nullable) {
            if (array_key_exists($nullable, $data)) {
                $attributes[$nullable] = $data[$nullable];
            }
        }

        if (array_key_exists('price', $data)) {
            $attributes['price'] = $data['price'];
        }

        foreach (['is_active', 'personalization_enabled', 'personalization_required'] as $flag) {
            if (array_key_exists($flag, $data)) {
                $attributes[$flag] = (bool) $data[$flag];
            }
        }

        if (array_key_exists('personalization_max_length', $data)) {
            $attributes['personalization_max_length'] = (int) $data['personalization_max_length'];
        }

        if (array_key_exists('sort_order', $data)) {
            $attributes['sort_order'] = (int) $data['sort_order'];
        }

        return $attributes;
    }

    /** @param array<string, mixed> $data */
    private function fullProductAttributes(array $data, ?int $productId): array
    {
        $slug = trim((string) ($data['slug'] ?? ''));

        if ($slug === '') {
            $slug = $this->uniqueSlug((string) $data['name'], $productId);
        }

        $sku = trim((string) ($data['sku'] ?? ''));

        return [
            'name' => $data['name'],
            'slug' => $slug,
            'sku' => $sku === '' ? null : $sku,
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'compare_at_price' => $data['compare_at_price'] ?? null,
            'is_active' => (bool) $data['is_active'],
            'personalization_enabled' => (bool) $data['personalization_enabled'],
            'personalization_label' => $data['personalization_label'] ?? null,
            'personalization_description' => $data['personalization_description'] ?? null,
            'personalization_required' => (bool) $data['personalization_required'],
            'personalization_max_length' => (int) $data['personalization_max_length'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    /** @param array<string, mixed> $data */
    private function syncNested(Product $product, array $data): void
    {
        if (array_key_exists('parameters', $data)) {
            $this->syncParameters($product, is_array($data['parameters']) ? $data['parameters'] : []);
        }

        if (array_key_exists('options', $data)) {
            $this->syncOptions($product, is_array($data['options']) ? $data['options'] : []);
        }

        if (array_key_exists('personalization_fields', $data)) {
            $this->syncPersonalizationFields(
                $product,
                is_array($data['personalization_fields']) ? $data['personalization_fields'] : []
            );
        }

        if (array_key_exists('variants', $data)) {
            $this->syncVariants($product, is_array($data['variants']) ? $data['variants'] : []);
        }
    }

    /** @param list<array<string, mixed>> $rows */
    private function syncParameters(Product $product, array $rows): void
    {
        $keep = [];

        foreach ($rows as $index => $row) {
            $parameter = $this->ownedParameter($product, $row['id'] ?? null);
            $parameter->forceFill([
                'product_id' => $product->id,
                'name' => $row['name'],
                'value' => $row['value'],
                'sort_order' => (int) ($row['sort_order'] ?? $index),
            ])->save();
            $keep[] = (int) $parameter->id;
        }

        $query = ProductParameter::query()->where('product_id', $product->id);

        if ($keep !== []) {
            $query->whereNotIn('id', $keep);
        }

        $query->delete();
    }

    /** @param list<array<string, mixed>> $rows */
    private function syncOptions(Product $product, array $rows): void
    {
        $keep = [];
        $usedSlugs = [];

        foreach ($rows as $index => $row) {
            $slug = $this->childSlug((string) ($row['slug'] ?? ''), (string) $row['name']);

            if (isset($usedSlugs[$slug])) {
                throw new ValidationException(['options.' . $index . '.slug' => ['Адресът на опцията се повтаря.']]);
            }

            $usedSlugs[$slug] = true;
            $option = $this->ownedOption($product, $row['id'] ?? null);
            $option->forceFill([
                'product_id' => $product->id,
                'name' => $row['name'],
                'slug' => $slug,
                'sort_order' => (int) ($row['sort_order'] ?? $index),
            ])->save();
            $this->syncOptionValues($option, is_array($row['values'] ?? null) ? $row['values'] : []);
            $keep[] = (int) $option->id;
        }

        $query = ProductOption::query()->where('product_id', $product->id);

        if ($keep !== []) {
            $query->whereNotIn('id', $keep);
        }

        $query->delete();
    }

    /** @param list<array<string, mixed>> $rows */
    private function syncOptionValues(ProductOption $option, array $rows): void
    {
        $keep = [];
        $usedSlugs = [];

        foreach ($rows as $index => $row) {
            $slug = $this->childSlug((string) ($row['slug'] ?? ''), (string) $row['name']);

            if (isset($usedSlugs[$slug])) {
                throw new ValidationException([
                    'options.*.values.' . $index . '.slug' => ['Адресът на стойността се повтаря.'],
                ]);
            }

            $usedSlugs[$slug] = true;
            $value = $this->ownedOptionValue($option, $row['id'] ?? null);
            $hex = trim((string) ($row['hex_color'] ?? ''));
            $value->forceFill([
                'product_option_id' => $option->id,
                'name' => $row['name'],
                'slug' => $slug,
                'hex_color' => $hex === '' ? null : strtoupper($hex),
                'sort_order' => (int) ($row['sort_order'] ?? $index),
            ])->save();
            $keep[] = (int) $value->id;
        }

        $query = ProductOptionValue::query()->where('product_option_id', $option->id);

        if ($keep !== []) {
            $query->whereNotIn('id', $keep);
        }

        $query->delete();
    }

    /** @param list<array<string, mixed>> $rows */
    private function syncPersonalizationFields(Product $product, array $rows): void
    {
        $keep = [];

        foreach ($rows as $index => $row) {
            $field = $this->ownedPersonalizationField($product, $row['id'] ?? null);
            $field->forceFill([
                'product_id' => $product->id,
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'field_type' => $row['field_type'] ?? ProductPersonalizationField::TYPE_TEXT,
                'is_required' => (bool) $row['is_required'],
                'max_length' => (int) $row['max_length'],
                'sort_order' => (int) ($row['sort_order'] ?? $index),
            ])->save();
            $keep[] = (int) $field->id;
        }

        $query = ProductPersonalizationField::query()->where('product_id', $product->id);

        if ($keep !== []) {
            $query->whereNotIn('id', $keep);
        }

        $query->delete();
    }

    /** @param list<array<string, mixed>> $rows */
    private function syncVariants(Product $product, array $rows): void
    {
        $product->load(['options.values']);
        $keep = [];
        $defaultSet = false;

        foreach ($rows as $index => $row) {
            $sku = trim((string) $row['sku']);
            $this->assertVariantSkuAvailable($sku, isset($row['id']) ? (int) $row['id'] : null);
            $variant = $this->ownedVariant($product, $row['id'] ?? null);
            $isDefault = !$defaultSet && (bool) ($row['is_default'] ?? false);
            $variant->forceFill([
                'product_id' => $product->id,
                'sku' => $sku,
                'name' => ($row['name'] ?? '') === '' ? null : $row['name'],
                'price' => $row['price'],
                'compare_at_price' => $row['compare_at_price'] ?? null,
                'stock' => (int) $row['stock'],
                'is_default' => $isDefault,
                'is_active' => (bool) $row['is_active'],
                'sort_order' => (int) ($row['sort_order'] ?? $index),
            ])->save();

            if ($isDefault) {
                $defaultSet = true;
            }

            $this->syncVariantValues(
                $product,
                $variant,
                is_array($row['option_values'] ?? null) ? $row['option_values'] : [],
                $index
            );
            $keep[] = (int) $variant->id;
        }

        if ($keep !== [] && !$defaultSet) {
            ProductVariant::query()->where('id', $keep[0])->update(['is_default' => true]);
        }

        $removed = ProductVariant::withTrashed()->where('product_id', $product->id);

        if ($keep !== []) {
            $removed->whereNotIn('id', $keep);
        }

        $removed->forceDelete();
    }

    /** @param list<array<string, mixed>> $rows */
    private function syncVariantValues(Product $product, ProductVariant $variant, array $rows, int $variantIndex): void
    {
        ProductVariantValue::query()->where('product_variant_id', $variant->id)->delete();

        $seenOptions = [];

        foreach ($rows as $rowIndex => $row) {
            [$option, $value] = $this->resolveOptionValue($product, $row, $variantIndex, $rowIndex);

            if (isset($seenOptions[$option->id])) {
                throw new ValidationException([
                    'variants.' . $variantIndex . '.option_values.' . $rowIndex => ['Опцията е подадена повече от веднъж.'],
                ]);
            }

            $seenOptions[$option->id] = true;
            ProductVariantValue::query()->create([
                'product_variant_id' => $variant->id,
                'product_option_id' => $option->id,
                'product_option_value_id' => $value->id,
            ]);
        }

        if ($product->options->isNotEmpty()) {
            foreach ($product->options as $option) {
                if (!isset($seenOptions[$option->id])) {
                    throw new ValidationException([
                        'variants.' . $variantIndex . '.option_values' => ['Вариантът трябва да има стойност за всяка опция.'],
                    ]);
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array{0: ProductOption, 1: ProductOptionValue}
     */
    private function resolveOptionValue(Product $product, array $row, int $variantIndex, int $rowIndex): array
    {
        $optionSlug = strtolower(trim((string) ($row['option'] ?? '')));
        $valueSlug = strtolower(trim((string) ($row['value'] ?? '')));
        $errorKey = 'variants.' . $variantIndex . '.option_values.' . $rowIndex;

        $option = $product->options->first(
            static fn (ProductOption $item): bool => $item->slug === $optionSlug
        );

        if ($option === null) {
            throw new ValidationException([$errorKey . '.option' => ['Опцията не съществува за този продукт.']]);
        }

        $value = $option->values->first(
            static fn (ProductOptionValue $item): bool => $item->slug === $valueSlug
        );

        if ($value === null) {
            throw new ValidationException([$errorKey . '.value' => ['Стойността не съществува за тази опция.']]);
        }

        return [$option, $value];
    }

    private function ownedParameter(Product $product, mixed $id): ProductParameter
    {
        if ($id === null || $id === '') {
            return new ProductParameter();
        }

        $parameter = ProductParameter::query()
            ->where('product_id', $product->id)
            ->where('id', (int) $id)
            ->first();

        if ($parameter === null) {
            throw new ValidationException(['parameters' => ['Параметърът не принадлежи на този продукт.']]);
        }

        return $parameter;
    }

    private function ownedOption(Product $product, mixed $id): ProductOption
    {
        if ($id === null || $id === '') {
            return new ProductOption();
        }

        $option = ProductOption::query()
            ->where('product_id', $product->id)
            ->where('id', (int) $id)
            ->first();

        if ($option === null) {
            throw new ValidationException(['options' => ['Опцията не принадлежи на този продукт.']]);
        }

        return $option;
    }

    private function ownedOptionValue(ProductOption $option, mixed $id): ProductOptionValue
    {
        if ($id === null || $id === '') {
            return new ProductOptionValue();
        }

        $value = ProductOptionValue::query()
            ->where('product_option_id', $option->id)
            ->where('id', (int) $id)
            ->first();

        if ($value === null) {
            throw new ValidationException(['options' => ['Стойността не принадлежи на тази опция.']]);
        }

        return $value;
    }

    private function ownedPersonalizationField(Product $product, mixed $id): ProductPersonalizationField
    {
        if ($id === null || $id === '') {
            return new ProductPersonalizationField();
        }

        $field = ProductPersonalizationField::query()
            ->where('product_id', $product->id)
            ->where('id', (int) $id)
            ->first();

        if ($field === null) {
            throw new ValidationException(['personalization_fields' => ['Полето не принадлежи на този продукт.']]);
        }

        return $field;
    }

    private function ownedVariant(Product $product, mixed $id): ProductVariant
    {
        if ($id === null || $id === '') {
            return new ProductVariant();
        }

        $variant = ProductVariant::withTrashed()
            ->where('product_id', $product->id)
            ->where('id', (int) $id)
            ->first();

        if ($variant === null) {
            throw new ValidationException(['variants' => ['Вариантът не принадлежи на този продукт.']]);
        }

        if ($variant->trashed()) {
            $variant->restore();
        }

        return $variant;
    }

    private function assertVariantSkuAvailable(string $sku, ?int $ignoreId): void
    {
        $query = ProductVariant::withTrashed()->where('sku', $sku);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw new ValidationException(['variants' => ['SKU на вариант вече е заето.']]);
        }
    }

    private function uniqueSlug(string $name, ?int $ignoreId): string
    {
        $base = Str::slug($name, '-', 'bg');
        $base = $base !== '' ? $base : 'product';
        $candidate = $base;
        $suffix = 2;

        while (
            Product::withTrashed()
                ->where('slug', $candidate)
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function childSlug(string $slug, string $name): string
    {
        $value = $slug !== '' ? $slug : Str::slug($name, '-', 'bg');
        $value = $value !== '' ? $value : 'option';

        return strtolower($value);
    }

    private function fresh(Product $product): Product
    {
        $fresh = Product::query()->find($product->id);

        return $fresh ?? $product;
    }
}
