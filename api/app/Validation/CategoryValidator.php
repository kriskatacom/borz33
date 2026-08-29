<?php

declare(strict_types=1);

namespace App\Validation;

use App\Core\ValidatorFactory;
use App\Exceptions\ValidationException;
use Illuminate\Validation\Rule;

class CategoryValidator
{
    /** @param array<string, mixed> $data */
    public function validate(array $data, ?int $categoryId = null): array
    {
        $data = $this->blankToNull($data, ['slug', 'parent_id', 'media_file_id']);
        $data = $this->normalizeNullableId($data, 'parent_id');
        $data = $this->normalizeNullableId($data, 'media_file_id');

        if (!array_key_exists('media_file_id', $data) && array_key_exists('media_id', $data)) {
            $data['media_file_id'] = $data['media_id'];
            $data = $this->blankToNull($data, ['media_file_id']);
            $data = $this->normalizeNullableId($data, 'media_file_id');
        }

        $rules = $this->rules($categoryId);

        if ($categoryId !== null) {
            $rules = $this->rulesForPresentKeys($rules, $data);
        }

        $validator = ValidatorFactory::make()->make($data, $rules, [], $this->attributes());

        if ($validator->fails()) {
            throw new ValidationException($validator->errors()->toArray());
        }

        return $validator->validated();
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

    /** @return array<string, mixed> */
    private function rules(?int $categoryId): array
    {
        $parentExists = Rule::exists('categories', 'id')->whereNull('deleted_at');

        if ($categoryId !== null) {
            $parentExists = $parentExists->whereNot('id', $categoryId);
        }

        return [
            'name' => ['required', 'string', 'max:191'],
            'slug' => [
                'nullable',
                'string',
                'max:191',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'is_active' => ['required', 'boolean'],
            'parent_id' => ['nullable', 'integer', 'min:1', $parentExists],
            'media_file_id' => ['nullable', 'integer', 'min:1', Rule::exists('media_files', 'id')],
            'sort_order' => ['nullable', 'integer', 'min:0'],
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
            'is_active' => 'активна',
            'parent_id' => 'родителска категория',
            'media_file_id' => 'изображение',
            'sort_order' => 'ред',
        ];
    }
}
