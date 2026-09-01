<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Exceptions\AuthException;
use App\Exceptions\ValidationException;
use App\Models\Product;
use App\Models\ProductAttributeTemplate;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductParameter;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Str;

class ProductAttributeTemplateService
{
    /** @return list<ProductAttributeTemplate> */
    public function all(): array
    {
        return ProductAttributeTemplate::query()->with('category')->orderBy('name')->get()->all();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): ProductAttributeTemplate
    {
        $template = new ProductAttributeTemplate();
        $template->forceFill($this->attributes($data))->save();
        return $template->fresh('category') ?? $template;
    }

    /** @param array<string, mixed> $data */
    public function update(ProductAttributeTemplate $template, array $data): ProductAttributeTemplate
    {
        $template->forceFill($this->attributes($data))->save();
        return $template->fresh('category') ?? $template;
    }

    public function find(int $id): ProductAttributeTemplate
    {
        $template = ProductAttributeTemplate::query()->find($id);
        if ($template === null) throw new AuthException('Шаблонът не е намерен.', 404);
        return $template;
    }

    /** @param list<string> $sections */
    public function apply(Product $product, int $templateId, array $sections): Product
    {
        $template = $this->find($templateId);
        $sections = array_values(array_intersect(['parameters', 'options', 'variants'], $sections));
        if ($sections === []) throw new ValidationException(['sections' => ['Изберете поне една секция от шаблона.']]);

        return Capsule::connection()->transaction(function () use ($product, $template, $sections): Product {
            if (in_array('parameters', $sections, true)) $this->mergeParameters($product, $template->parameters ?? []);
            if (in_array('options', $sections, true) || in_array('variants', $sections, true)) $this->mergeOptions($product, $template->options ?? []);
            if (in_array('variants', $sections, true)) $this->generateMissingVariants($product);

            return $product->fresh(['parameters', 'options.values', 'variants.optionValues', 'variants.image', 'frontImage', 'galleryImages', 'category']) ?? $product;
        });
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function attributes(array $data): array
    {
        $result = [];
        foreach (['name', 'category_id', 'parameters', 'options'] as $key) if (array_key_exists($key, $data)) $result[$key] = $data[$key];
        if (array_key_exists('category_id', $result) && !$result['category_id']) $result['category_id'] = null;
        if (array_key_exists('parameters', $result)) $result['parameters'] = $this->normaliseParameters(is_array($result['parameters']) ? $result['parameters'] : []);
        if (array_key_exists('options', $result)) $result['options'] = $this->normaliseOptions(is_array($result['options']) ? $result['options'] : []);
        return $result;
    }

    /** @param list<array<string, mixed>> $rows @return list<array<string, string>> */
    private function normaliseParameters(array $rows): array
    {
        return array_values(array_map(static fn (array $row): array => ['name' => trim((string) $row['name']), 'value' => trim((string) $row['value'])], $rows));
    }

    /** @param list<array<string, mixed>> $rows @return list<array<string, mixed>> */
    private function normaliseOptions(array $rows): array
    {
        $used = [];
        $result = [];
        foreach ($rows as $row) {
            $name = trim((string) $row['name']);
            $slug = $this->uniqueSlug((string) ($row['slug'] ?? ''), $name, $used);
            $values = []; $usedValues = [];
            foreach (is_array($row['values'] ?? null) ? $row['values'] : [] as $value) {
                $valueName = trim((string) $value['name']);
                $valueSlug = $this->uniqueSlug((string) ($value['slug'] ?? ''), $valueName, $usedValues);
                $hex = strtoupper(trim((string) ($value['hex_color'] ?? '')));
                $values[] = ['name' => $valueName, 'slug' => $valueSlug, 'hex_color' => $hex === '' ? null : $hex];
            }
            $result[] = ['name' => $name, 'slug' => $slug, 'values' => $values];
        }
        return $result;
    }

    /** @param list<array<string, string>> $rows */
    private function mergeParameters(Product $product, array $rows): void
    {
        $existing = ProductParameter::query()->where('product_id', $product->id)->get()->keyBy(fn (ProductParameter $item) => mb_strtolower($item->name));
        foreach ($rows as $index => $row) {
            if ($existing->has(mb_strtolower($row['name']))) continue;
            ProductParameter::query()->create(['product_id' => $product->id, 'name' => $row['name'], 'value' => $row['value'], 'sort_order' => $existing->count() + $index]);
        }
    }

    /** @param list<array<string, mixed>> $rows */
    private function mergeOptions(Product $product, array $rows): void
    {
        $existing = ProductOption::query()->where('product_id', $product->id)->with('values')->get()->keyBy('slug');
        foreach ($rows as $index => $row) {
            $option = $existing->get($row['slug']);
            if (!$option) {
                $option = ProductOption::query()->create(['product_id' => $product->id, 'name' => $row['name'], 'slug' => $row['slug'], 'sort_order' => $existing->count() + $index]);
                $existing->put($option->slug, $option); $option->setRelation('values', collect());
            }
            $values = $option->relationLoaded('values') ? $option->values->keyBy('slug') : collect();
            foreach ($row['values'] as $valueIndex => $rowValue) {
                if ($values->has($rowValue['slug'])) continue;
                $created = ProductOptionValue::query()->create(['product_option_id' => $option->id, 'name' => $rowValue['name'], 'slug' => $rowValue['slug'], 'hex_color' => $rowValue['hex_color'], 'sort_order' => $values->count() + $valueIndex]);
                $values->put($created->slug, $created);
            }
        }
    }

    private function generateMissingVariants(Product $product): void
    {
        $product->load(['options.values', 'variants.variantValues']);
        if ($product->options->isEmpty()) return;
        foreach ($product->options as $option) if ($option->values->isEmpty()) return;
        $combinations = [[]];
        foreach ($product->options as $option) {
            $next = [];
            foreach ($combinations as $combination) foreach ($option->values as $value) $next[] = [...$combination, [$option, $value]];
            $combinations = $next;
            if (count($combinations) > 100) throw new ValidationException(['variants' => ['Шаблонът би създал повече от 100 варианта. Изберете по-малко стойности.']]);
        }
        $existing = [];
        foreach ($product->variants as $variant) {
            $ids = $variant->variantValues->map(fn (ProductVariantValue $item) => $item->product_option_value_id)->sort()->implode('-');
            $existing[$ids] = true;
        }
        $hasDefault = $product->variants->contains(fn (ProductVariant $item) => $item->is_default);
        foreach ($combinations as $index => $combination) {
            $ids = collect($combination)->map(fn (array $item) => $item[1]->id)->sort()->implode('-');
            if (isset($existing[$ids])) continue;
            $labels = array_map(fn (array $item) => $item[1]->name, $combination);
            $variant = ProductVariant::query()->create([
                'product_id' => $product->id, 'sku' => $this->nextVariantSku($product, $combination), 'name' => implode(' / ', $labels),
                'price' => $product->price, 'stock' => 0, 'is_default' => !$hasDefault, 'is_active' => true, 'sort_order' => $product->variants->count() + $index,
            ]);
            foreach ($combination as [$option, $value]) ProductVariantValue::query()->create(['product_variant_id' => $variant->id, 'product_option_id' => $option->id, 'product_option_value_id' => $value->id]);
            $hasDefault = true;
        }
    }

    /** @param list<array{0: ProductOption, 1: ProductOptionValue}> $combination */
    private function nextVariantSku(Product $product, array $combination): string
    {
        $base = trim((string) $product->sku) ?: 'P' . $product->id;
        $suffix = implode('-', array_map(fn (array $item) => strtoupper($item[1]->slug), $combination));
        $candidate = substr($base . '-' . $suffix, 0, 64); $number = 2;
        while (ProductVariant::withTrashed()->where('sku', $candidate)->exists()) $candidate = substr($base . '-' . $suffix . '-' . $number++, 0, 64);
        return $candidate;
    }

    /** @param array<string, bool> $used */
    private function uniqueSlug(string $requested, string $fallback, array &$used): string
    {
        $base = Str::slug($requested !== '' ? $requested : $fallback) ?: 'option'; $slug = $base; $n = 2;
        while (isset($used[$slug])) $slug = $base . '-' . $n++;
        $used[$slug] = true; return $slug;
    }
}
