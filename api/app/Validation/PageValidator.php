<?php

declare(strict_types=1);

namespace App\Validation;

use App\Core\ValidatorFactory;
use App\Exceptions\ValidationException;
use App\Models\PageField;
use Illuminate\Validation\Rule;

class PageValidator
{
    /** @param array<string, mixed> $data */
    public function validate(array $data, ?int $pageId = null): array
    {
        $data = $this->blankToNull($data, ['slug', 'content', 'meta_title', 'meta_description', 'parent_id', 'page_template_id']);
        $data = $this->normalizeSlug($data);
        $data = $this->normalizeParent($data);
        $data = $this->normalizeFields($data);
        $rules = $this->rules($pageId);

        if ($pageId !== null) {
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
    private function normalizeFields(array $data): array
    {
        if (!isset($data['fields']) || !is_array($data['fields'])) {
            return $data;
        }

        foreach ($data['fields'] as $index => $field) {
            if (!is_array($field)) {
                continue;
            }

            if (!array_key_exists('media_file_id', $field) && array_key_exists('media_id', $field)) {
                $data['fields'][$index]['media_file_id'] = $field['media_id'];
            }

            if (array_key_exists('value', $field) && $field['value'] === '') {
                $data['fields'][$index]['value'] = null;
            }

            if (array_key_exists('slug', $field) && $field['slug'] === '') {
                $data['fields'][$index]['slug'] = null;
            }
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeParent(array $data): array
    {
        if (!array_key_exists('parent_id', $data)) {
            return $data;
        }

        $value = $data['parent_id'];

        if ($value === '' || $value === 'none' || $value === 'null' || $value === 0 || $value === '0') {
            $data['parent_id'] = null;
        }

        return $data;
    }

    /** @param array<string, mixed> $data
     *  @return array<string, mixed>
     */
    private function normalizeSlug(array $data): array
    {
        if (!isset($data['slug']) || !is_string($data['slug'])) {
            return $data;
        }

        $data['slug'] = strtolower(trim((string) preg_replace('#/+#', '/', trim($data['slug'])), '/'));

        return $data;
    }

    /** @return array<string, mixed> */
    private function rules(?int $pageId): array
    {
        $parentExists = Rule::exists('pages', 'id')->whereNull('deleted_at');

        if ($pageId !== null) {
            $parentExists = $parentExists->whereNot('id', $pageId);
        }

        return [
            'title' => ['required', 'string', 'max:191'],
            'slug' => [
                'nullable',
                'string',
                'max:191',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*(?:\/[a-z0-9]+(?:-[a-z0-9]+)*)*$/',
                Rule::unique('pages', 'slug')->ignore($pageId),
            ],
            'is_active' => ['required', 'boolean'],
            'parent_id' => ['nullable', 'integer', 'min:1', $parentExists],
            'page_template_id' => ['nullable', 'integer', 'min:1', Rule::exists('page_templates', 'id')],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'content' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:191'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'fields' => ['nullable', 'array'],
            'fields.*.id' => ['nullable', 'integer', 'min:1'],
            'fields.*.name' => ['required', 'string', 'max:191'],
            'fields.*.slug' => ['nullable', 'string', 'max:191', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'fields.*.field_type' => [
                'required',
                'string',
                Rule::in([PageField::TYPE_TEXT, PageField::TYPE_TEXTAREA, PageField::TYPE_FILE]),
            ],
            'fields.*.value' => ['nullable', 'string'],
            'fields.*.media_file_id' => ['nullable', 'integer', 'min:1', Rule::exists('media_files', 'id')],
            'fields.*.is_required' => ['required', 'boolean'],
            'fields.*.sort_order' => ['nullable', 'integer', 'min:0'],
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
            'title' => 'заглавие',
            'slug' => 'адрес',
            'is_active' => 'активна',
            'parent_id' => 'родителска страница',
            'page_template_id' => 'шаблон',
            'sort_order' => 'ред',
            'content' => 'основно съдържание',
            'meta_title' => 'SEO заглавие',
            'meta_description' => 'SEO описание',
            'fields' => 'полета',
            'fields.*.name' => 'име на поле',
            'fields.*.slug' => 'адрес на поле',
            'fields.*.field_type' => 'тип на поле',
            'fields.*.value' => 'стойност',
            'fields.*.media_file_id' => 'файл',
            'fields.*.is_required' => 'задължително поле',
        ];
    }
}
