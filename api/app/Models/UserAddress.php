<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    public const TYPE_BILLING = 'billing';

    public const PARTY_PERSON = 'person';

    public const PARTY_COMPANY = 'company';

    protected $fillable = [
        'user_id',
        'type',
        'party',
        'label',
        'is_default',
        'first_name',
        'last_name',
        'company_name',
        'eik',
        'vat_number',
        'mol',
        'line1',
        'city',
        'postal_code',
        'country',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompany(): bool
    {
        return $this->party === self::PARTY_COMPANY;
    }

    public function title(): string
    {
        $label = trim((string) $this->label);

        if ($label !== '') {
            return $label;
        }

        if ($this->isCompany()) {
            return trim((string) $this->company_name);
        }

        return trim(trim((string) $this->first_name) . ' ' . trim((string) $this->last_name));
    }

    /** @return list<string> */
    public function lines(): array
    {
        $lines = [];

        if ($this->isCompany()) {
            $company = trim((string) $this->company_name);

            if ($company !== '') {
                $lines[] = $company;
            }

            $eik = trim((string) $this->eik);

            if ($eik !== '') {
                $lines[] = 'ЕИК ' . $eik;
            }

            $vat = trim((string) $this->vat_number);

            if ($vat !== '') {
                $lines[] = 'ДДС № ' . $vat;
            }

            $mol = trim((string) $this->mol);

            if ($mol !== '') {
                $lines[] = 'МОЛ ' . $mol;
            }
        } else {
            $name = trim(trim((string) $this->first_name) . ' ' . trim((string) $this->last_name));

            if ($name !== '') {
                $lines[] = $name;
            }
        }

        $lines[] = trim((string) $this->line1);
        $lines[] = trim(trim((string) $this->postal_code) . ' ' . trim((string) $this->city));
        $country = trim((string) $this->country);

        if ($country !== '') {
            $lines[] = $country;
        }

        return array_values(array_filter($lines, static fn (string $line): bool => $line !== ''));
    }
}
