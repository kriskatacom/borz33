<?php

declare(strict_types=1);

namespace App\Validation;

use App\Core\ValidatorFactory;
use App\Exceptions\ValidationException;
use App\Models\ProductPersonalizationField;
use Illuminate\Validation\Rule;

class ProductValidator
{
    /** @param array<string, mixed> $data */
    public function validate(array $data, ?int $productId = null): array
    {
        $data = $this->blankToNull($data, [
            'slug',
            'sku',
            'category_id',
            'short_description',
            'description',
            'compare_at_price',
            'personalization_label',
            'personalization_description',
        ]);
        $data = $this->normalizeNullableId($data, 'category_id');

        $rules = $this->rules($productId);

        if ($productId !== null) {
            $rules = $this->rulesForPresentKeys($rules, $data);
        }

        $validator = ValidatorFactory::make()->make($data, $rules, [], $this->attributes());

        if ($validator->fails()) {
            throw new ValidationException($validator->errors()->toArray());
        }

        return $validator->validated();
    }

    /** @return array<string, mixed> */
    private function rules(?int $productId): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'slug' => [
                'nullable',
                'string',
                'max:191',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'sku' => ['nullable', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($productId)],
            'category_id' => ['nullable', 'integer', 'min:1', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'weight_grams' => ['required', 'integer', 'min:1', 'max:1000000'],
            'is_active' => ['required', 'boolean'],
            'personalization_enabled' => ['required', 'boolean'],
            'personalization_label' => ['nullable', 'string', 'max:191'],
            'personalization_description' => ['nullable', 'string'],
            'personalization_required' => ['required', 'boolean'],
            'personalization_max_length' => ['required', 'integer', 'min:1', 'max:2000'],
            'personalization_override' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'parameters' => ['nullable', 'array'],
            'parameters.*.id' => ['nullable', 'integer', 'min:1'],
            'parameters.*.name' => ['required', 'string', 'max:191'],
            'parameters.*.value' => ['required', 'string', 'max:255'],
            'parameters.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'options' => ['nullable', 'array'],
            'options.*.id' => ['nullable', 'integer', 'min:1'],
            'options.*.name' => ['required', 'string', 'max:191'],
            'options.*.slug' => ['nullable', 'string', 'max:191', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'options.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'options.*.values' => ['nullable', 'array'],
            'options.*.values.*.id' => ['nullable', 'integer', 'min:1'],
            'options.*.values.*.name' => ['required', 'string', 'max:191'],
            'options.*.values.*.slug' => ['nullable', 'string', 'max:191', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'options.*.values.*.hex_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'options.*.values.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer', 'min:1'],
            'variants.*.sku' => ['required', 'string', 'max:64', 'distinct'],
            'variants.*.name' => ['nullable', 'string', 'max:191'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.is_default' => ['nullable', 'boolean'],
            'variants.*.is_active' => ['required', 'boolean'],
            'variants.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'variants.*.option_values' => ['nullable', 'array'],
            'variants.*.option_values.*.option' => ['nullable', 'string', 'max:191'],
            'variants.*.option_values.*.value' => ['nullable', 'string', 'max:191'],
            'personalization_fields' => ['nullable', 'array'],
            'personalization_fields.*.id' => ['nullable', 'integer', 'min:1'],
            'personalization_fields.*.name' => ['required', 'string', 'max:191'],
            'personalization_fields.*.description' => ['nullable', 'string'],
            'personalization_fields.*.field_type' => [
                'nullable',
                'string',
                Rule::in([ProductPersonalizationField::TYPE_TEXT, ProductPersonalizationField::TYPE_TEXTAREA]),
            ],
            'personalization_fields.*.is_required' => ['required', 'boolean'],
            'personalization_fields.*.max_length' => ['required', 'integer', 'min:1', 'max:2000'],
            'personalization_fields.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string> $keys
     * @return array<string, mixed>
     */
    private function blankToNull(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeNullableId(array $data, string $key): array
    {
        if (!array_key_exists($key, $data)) {
            return $data;
        }

        $value = $data[$key];

        if ($value === '' || $value === 'none' || $value === 'null' || $value === 0 || $value === '0') {
            $data[$key] = null;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function rulesForPresentKeys(array $rules, array $data): array
    {
        $filtered = [];

        foreach ($rules as $key => $rule) {
            $root = explode('.', $key, 2)[0];

            if (array_key_exists($root, $data)) {
                $filtered[$key] = $rule;
            }
        }

        return $filtered;
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        return [
            'name' => 'име',
            'slug' => 'адрес',
            'sku' => 'SKU',
            'category_id' => 'категория',
            'short_description' => 'кратко описание',
            'description' => 'описание',
            'price' => 'цена',
            'compare_at_price' => 'стара цена',
            'weight_grams' => 'тегло',
            'is_active' => 'активен',
            'personalization_enabled' => 'персонализация',
            'personalization_label' => 'етикет за персонализация',
            'personalization_description' => 'описание за персонализация',
            'personalization_required' => 'задължителна персонализация',
            'personalization_max_length' => 'макс. дължина на персонализация',
            'sort_order' => 'ред',
            'parameters' => 'параметри',
            'parameters.*.name' => 'име на параметър',
            'parameters.*.value' => 'стойност на параметър',
            'options' => 'опции',
            'options.*.name' => 'име на опция',
            'options.*.slug' => 'адрес на опция',
            'options.*.values.*.name' => 'стойност на опция',
            'options.*.values.*.slug' => 'адрес на стойност',
            'options.*.values.*.hex_color' => 'цвят',
            'variants' => 'варианти',
            'variants.*.sku' => 'SKU на вариант',
            'variants.*.name' => 'име на вариант',
            'variants.*.price' => 'цена на вариант',
            'variants.*.stock' => 'наличност',
            'personalization_fields' => 'полета за персонализация',
            'personalization_fields.*.name' => 'име на поле',
            'personalization_fields.*.field_type' => 'тип на поле',
            'personalization_fields.*.max_length' => 'макс. дължина',
        ];
    }
}
