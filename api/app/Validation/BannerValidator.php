<?php

declare(strict_types=1);

namespace App\Validation;

use App\Core\ValidatorFactory;
use App\Exceptions\ValidationException;
use App\Models\Banner;
use App\Support\SafeHtml;
use Illuminate\Validation\Rule;

class BannerValidator
{
    /** @param array<string, mixed> $data */
    public function validate(array $data, ?int $bannerId = null): array
    {
        $data = $this->blankToNull($data, ['slug', 'height', 'media_file_id']);
        $data = $this->normalizeNullableId($data, 'media_file_id');

        if (!array_key_exists('media_file_id', $data) && array_key_exists('media_id', $data)) {
            $data['media_file_id'] = $data['media_id'];
            $data = $this->blankToNull($data, ['media_file_id']);
            $data = $this->normalizeNullableId($data, 'media_file_id');
        }

        $data = $this->normalizeButtons($data);
        $rules = $this->rules($bannerId);

        if ($bannerId !== null) {
            $rules = $this->rulesForPresentKeys($rules, $data);
        }

        $validator = ValidatorFactory::make()->make($data, $rules, [], $this->attributes());

        if ($validator->fails()) {
            throw new ValidationException($validator->errors()->toArray());
        }

        $validated = $validator->validated();

        if (array_key_exists('text', $validated) && is_string($validated['text'])) {
            $validated['text'] = SafeHtml::bannerText($validated['text']);

            if (SafeHtml::isBlank($validated['text'])) {
                throw new ValidationException(['text' => ['Текстът е задължителен.']]);
            }
        }

        return $validated;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeButtons(array $data): array
    {
        if (!isset($data['buttons']) || !is_array($data['buttons'])) {
            return $data;
        }

        foreach ($data['buttons'] as $index => $button) {
            if (!is_array($button)) {
                continue;
            }

            if (array_key_exists('url', $button) && is_string($button['url'])) {
                $data['buttons'][$index]['url'] = trim($button['url']);
            }

            if (array_key_exists('label', $button) && is_string($button['label'])) {
                $data['buttons'][$index]['label'] = trim($button['label']);
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

    /** @return array<string, mixed> */
    private function rules(?int $bannerId): array
    {
        return [
            'title' => ['required', 'string', 'max:191'],
            'slug' => [
                'nullable',
                'string',
                'max:191',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('banners', 'slug')->ignore($bannerId),
            ],
            'text' => ['required', 'string'],
            'layout' => ['required', 'string', Rule::in(Banner::LAYOUTS)],
            'height' => ['nullable', 'integer', 'min:120', 'max:1000'],
            'width_mode' => ['required', 'string', Rule::in(['container', 'full'])],
            'image_position' => ['required', 'string', Rule::in(array_keys(Banner::IMAGE_POSITIONS))],
            'content_position' => ['required', 'string', Rule::in(array_keys(Banner::CONTENT_POSITIONS))],
            'is_active' => ['required', 'boolean'],
            'media_file_id' => ['required', 'integer', 'min:1', Rule::exists('media_files', 'id')],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'buttons' => ['nullable', 'array'],
            'buttons.*.id' => ['nullable', 'integer', 'min:1'],
            'buttons.*.label' => ['required', 'string', 'max:191'],
            'buttons.*.url' => ['required', 'string', 'max:500'],
            'buttons.*.open_in_new_tab' => ['nullable', 'boolean'],
            'buttons.*.sort_order' => ['nullable', 'integer', 'min:0'],
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
            'text' => 'текст',
            'layout' => 'дизайн',
            'height' => 'височина',
            'width_mode' => 'ширина на банера',
            'image_position' => 'позиция на изображението',
            'content_position' => 'позиция на съдържанието',
            'is_active' => 'активен',
            'media_file_id' => 'изображение',
            'sort_order' => 'ред',
            'buttons' => 'бутони',
            'buttons.*.label' => 'текст на бутон',
            'buttons.*.url' => 'адрес на бутон',
            'buttons.*.open_in_new_tab' => 'отваряне в нов таб',
        ];
    }
}
