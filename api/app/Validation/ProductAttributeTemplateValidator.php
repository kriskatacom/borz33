<?php

declare(strict_types=1);

namespace App\Validation;

use App\Core\ValidatorFactory;
use App\Exceptions\ValidationException;
use Illuminate\Validation\Rule;

class ProductAttributeTemplateValidator
{
    /** @param array<string, mixed> $data */
    public function validate(array $data, bool $partial = false): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:191'],
            'category_id' => ['nullable', 'integer', 'min:1', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'parameters' => ['nullable', 'array'],
            'parameters.*.name' => ['required', 'string', 'max:191'],
            'parameters.*.value' => ['required', 'string', 'max:255'],
            'options' => ['nullable', 'array', 'max:4'],
            'options.*.name' => ['required', 'string', 'max:191'],
            'options.*.slug' => ['nullable', 'string', 'max:191', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'options.*.values' => ['required', 'array', 'min:1', 'max:30'],
            'options.*.values.*.name' => ['required', 'string', 'max:191'],
            'options.*.values.*.slug' => ['nullable', 'string', 'max:191', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'options.*.values.*.hex_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];

        if ($partial) {
            $rules = array_filter($rules, static fn (array $rule, string $key): bool => array_key_exists(explode('.', $key, 2)[0], $data), ARRAY_FILTER_USE_BOTH);
        }

        $validator = ValidatorFactory::make()->make($data, $rules, [], [
            'name' => 'име на шаблона', 'category_id' => 'категория', 'parameters' => 'параметри', 'options' => 'опции',
            'parameters.*.name' => 'име на параметър', 'parameters.*.value' => 'стойност на параметър',
            'options.*.name' => 'име на опция', 'options.*.values' => 'стойности на опция',
            'options.*.values.*.name' => 'стойност на опция', 'options.*.values.*.hex_color' => 'цвят',
        ]);

        if ($validator->fails()) throw new ValidationException($validator->errors()->toArray());
        return $validator->validated();
    }
}
