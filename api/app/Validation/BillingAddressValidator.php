<?php

declare(strict_types=1);

namespace App\Validation;

use App\Core\ValidatorFactory;
use App\Exceptions\ValidationException;
use App\Models\UserAddress;
use Illuminate\Validation\Rule;
use Store\Support\EuropeanCountries;

class BillingAddressValidator
{
    /** @param array<string, mixed> $data */
    public function validate(array $data): array
    {
        $data = $this->normalize($data);

        $validator = ValidatorFactory::make()->make($data, [
            'party' => ['required', 'string', Rule::in([UserAddress::PARTY_PERSON, UserAddress::PARTY_COMPANY])],
            'label' => ['nullable', 'string', 'max:80'],
            'first_name' => ['required_if:party,' . UserAddress::PARTY_PERSON, 'nullable', 'string', 'max:100'],
            'last_name' => ['required_if:party,' . UserAddress::PARTY_PERSON, 'nullable', 'string', 'max:100'],
            'company_name' => ['required_if:party,' . UserAddress::PARTY_COMPANY, 'nullable', 'string', 'max:191'],
            'eik' => ['required_if:party,' . UserAddress::PARTY_COMPANY, 'nullable', 'string', 'regex:/^\d{9}$/'],
            'vat_number' => ['nullable', 'string', 'regex:/^BG\d{9,10}$/'],
            'mol' => ['required_if:party,' . UserAddress::PARTY_COMPANY, 'nullable', 'string', 'max:191'],
            'line1' => ['required', 'string', 'max:191'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'regex:/^\d{4}$/'],
            'country' => ['required', 'string', 'max:80', Rule::in(EuropeanCountries::names())],
            'is_default' => ['required', 'boolean'],
        ], [
            'eik.regex' => 'ЕИК трябва да е 9 цифри.',
            'vat_number.regex' => 'ДДС номерът трябва да е във формат BG и 9 или 10 цифри.',
            'postal_code.regex' => 'Пощенският код трябва да е 4 цифри.',
        ], [
            'party' => 'тип клиент',
            'label' => 'име на адреса',
            'first_name' => 'име',
            'last_name' => 'фамилия',
            'company_name' => 'име на фирмата',
            'eik' => 'ЕИК',
            'vat_number' => 'ДДС номер',
            'mol' => 'МОЛ',
            'line1' => 'адрес',
            'city' => 'град',
            'postal_code' => 'пощенски код',
            'country' => 'държава',
            'is_default' => 'основен адрес',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $party = (string) $validated['party'];

        if ($party === UserAddress::PARTY_PERSON) {
            $validated['company_name'] = null;
            $validated['eik'] = null;
            $validated['vat_number'] = null;
            $validated['mol'] = null;
        } else {
            $validated['first_name'] = null;
            $validated['last_name'] = null;
        }

        $validated['label'] = $this->nullableString($validated['label'] ?? null);
        $validated['vat_number'] = $this->nullableString($validated['vat_number'] ?? null);

        return $validated;
    }

    /** @param array<string, mixed> $data */
    private function normalize(array $data): array
    {
        foreach (['label', 'first_name', 'last_name', 'company_name', 'mol', 'line1', 'city', 'country'] as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            $value = is_string($data[$key]) ? trim($data[$key]) : '';
            $data[$key] = $value === '' ? null : $value;
        }

        $eik = preg_replace('/\D+/', '', (string) ($data['eik'] ?? '')) ?? '';
        $data['eik'] = $eik === '' ? null : $eik;

        $vat = strtoupper(preg_replace('/\s+/', '', (string) ($data['vat_number'] ?? '')) ?? '');
        $data['vat_number'] = $vat === '' ? null : $vat;

        $postal = preg_replace('/\s+/', '', (string) ($data['postal_code'] ?? '')) ?? '';
        $data['postal_code'] = $postal;

        if (($data['country'] ?? null) === null) {
            $data['country'] = 'България';
        }

        $data['is_default'] = in_array($data['is_default'] ?? false, [true, 1, '1', 'on', 'true'], true);

        return $data;
    }

    private function nullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
