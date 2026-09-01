<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\Exceptions\ValidationException;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Collection;

class BillingAddressService
{
    public const MAX = 20;

    /** @return Collection<int, UserAddress> */
    public function list(User $user): Collection
    {
        return UserAddress::query()
            ->where('user_id', $user->id)
            ->where('type', UserAddress::TYPE_BILLING)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();
    }

    public function findOwned(User $user, int $id): ?UserAddress
    {
        return UserAddress::query()
            ->where('user_id', $user->id)
            ->where('type', UserAddress::TYPE_BILLING)
            ->where('id', $id)
            ->first();
    }

    /** @param array<string, mixed> $data */
    public function create(User $user, array $data): UserAddress
    {
        $count = $this->list($user)->count();
        $hasPartyAddress = $this->listForParty($user, (string) $data['party'])->isNotEmpty();

        if ($count >= self::MAX) {
            throw new ValidationException(['label' => ['Може да запишете най-много ' . self::MAX . ' адреса за фактуриране.']]);
        }

        return Capsule::connection()->transaction(function () use ($user, $data, $hasPartyAddress): UserAddress {
            $makeDefault = (bool) ($data['is_default'] ?? false) || !$hasPartyAddress;

            if ($makeDefault) {
                $this->clearDefault($user, (string) $data['party']);
            }

            return UserAddress::query()->create([
                ...$this->attributes($data),
                'user_id' => $user->id,
                'type' => UserAddress::TYPE_BILLING,
                'is_default' => $makeDefault,
            ]);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $user, UserAddress $address, array $data): UserAddress
    {
        return Capsule::connection()->transaction(function () use ($user, $address, $data): UserAddress {
            $makeDefault = (bool) ($data['is_default'] ?? false);

            if ($makeDefault) {
                $this->clearDefault($user, (string) $data['party'], (int) $address->id);
            }

            $address->forceFill([
                ...$this->attributes($data),
                'is_default' => $makeDefault,
            ])->save();

            return $address->refresh();
        });
    }

    public function delete(User $user, UserAddress $address): void
    {
        Capsule::connection()->transaction(function () use ($user, $address): void {
            $wasDefault = $address->is_default;
            $address->delete();

            if (!$wasDefault) {
                return;
            }

            $next = $this->listForParty($user, (string) $address->party)->first();

            if ($next !== null) {
                $next->forceFill(['is_default' => true])->save();
            }
        });
    }

    public function setDefault(User $user, UserAddress $address): UserAddress
    {
        return Capsule::connection()->transaction(function () use ($user, $address): UserAddress {
            $this->clearDefault($user, (string) $address->party);
            $address->forceFill(['is_default' => true])->save();

            return $address->refresh();
        });
    }

    /**
     * Save checkout data in the customer profile without duplicates. Physical and
     * company addresses keep independent defaults, so either checkout mode can
     * restore the appropriate address next time.
     *
     * @param array<string, string> $form
     */
    public function rememberOrderAddresses(User $user, array $form, bool $wantsInvoice): void
    {
        Capsule::connection()->transaction(function () use ($user, $form, $wantsInvoice): void {
            $delivery = [
                'party' => UserAddress::PARTY_PERSON,
                'first_name' => $form['first_name'], 'last_name' => $form['last_name'],
                'company_name' => null, 'eik' => null, 'vat_number' => null, 'mol' => null,
                'line1' => $form['address_line'], 'city' => $form['city'],
                'postal_code' => $form['postal_code'], 'country' => $form['country'],
            ];

            if (!$wantsInvoice) {
                $this->createIfMissing($user, $delivery);
                return;
            }

            $billing = [
                'party' => UserAddress::PARTY_COMPANY,
                'first_name' => null, 'last_name' => null,
                'company_name' => $form['invoice_company'], 'eik' => $form['invoice_eik'],
                'vat_number' => $form['invoice_vat_number'] !== '' ? strtoupper($form['invoice_vat_number']) : null,
                'mol' => $form['invoice_mol'], 'line1' => $form['invoice_address'],
                'city' => $form['city'], 'postal_code' => $form['postal_code'], 'country' => $form['country'],
            ];
            $this->createIfMissing($user, $billing);

            // A separate delivery location is kept as a physical-person address.
            if (!$this->sameLocation($delivery, $billing)) {
                $this->createIfMissing($user, $delivery);
            }
        });
    }

    /** @return array<string, mixed> */
    public function emptyForm(User $user): array
    {
        return [
            'party' => UserAddress::PARTY_PERSON,
            'label' => '',
            'first_name' => (string) $user->first_name,
            'last_name' => (string) $user->last_name,
            'company_name' => '',
            'eik' => '',
            'vat_number' => '',
            'mol' => '',
            'line1' => '',
            'city' => '',
            'postal_code' => '',
            'country' => 'България',
            'is_default' => false,
        ];
    }

    /** @return array<string, mixed> */
    public function toForm(UserAddress $address): array
    {
        return [
            'party' => (string) $address->party,
            'label' => (string) ($address->label ?? ''),
            'first_name' => (string) ($address->first_name ?? ''),
            'last_name' => (string) ($address->last_name ?? ''),
            'company_name' => (string) ($address->company_name ?? ''),
            'eik' => (string) ($address->eik ?? ''),
            'vat_number' => (string) ($address->vat_number ?? ''),
            'mol' => (string) ($address->mol ?? ''),
            'line1' => (string) $address->line1,
            'city' => (string) $address->city,
            'postal_code' => (string) $address->postal_code,
            'country' => (string) $address->country,
            'is_default' => (bool) $address->is_default,
        ];
    }

    /** @param array<string, mixed> $data */
    private function attributes(array $data): array
    {
        return [
            'party' => $data['party'],
            'label' => $data['label'] ?? null,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'eik' => $data['eik'] ?? null,
            'vat_number' => $data['vat_number'] ?? null,
            'mol' => $data['mol'] ?? null,
            'line1' => $data['line1'],
            'city' => $data['city'],
            'postal_code' => $data['postal_code'],
            'country' => $data['country'],
        ];
    }

    /** @return Collection<int, UserAddress> */
    private function listForParty(User $user, string $party): Collection
    {
        return UserAddress::query()
            ->where('user_id', $user->id)
            ->where('type', UserAddress::TYPE_BILLING)
            ->where('party', $party)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();
    }

    /** @param array<string, string|null> $data */
    private function createIfMissing(User $user, array $data): ?UserAddress
    {
        $party = (string) $data['party'];
        $existing = $this->listForParty($user, $party);
        foreach ($existing as $address) {
            if ($this->sameAddress($address, $data)) return $address;
        }
        if ($this->list($user)->count() >= self::MAX) return null;

        $this->clearDefault($user, $party);
        return UserAddress::query()->create([
            ...$data,
            'user_id' => $user->id,
            'type' => UserAddress::TYPE_BILLING,
            'label' => null,
            'is_default' => true,
        ]);
    }

    /** @param array<string, string|null> $data */
    private function sameAddress(UserAddress $address, array $data): bool
    {
        foreach (['party', 'first_name', 'last_name', 'company_name', 'eik', 'vat_number', 'mol', 'line1', 'city', 'postal_code', 'country'] as $field) {
            if ($this->normalise($address->{$field}) !== $this->normalise($data[$field] ?? null)) return false;
        }
        return true;
    }

    /** @param array<string, string|null> $first @param array<string, string|null> $second */
    private function sameLocation(array $first, array $second): bool
    {
        foreach (['line1', 'city', 'postal_code', 'country'] as $field) {
            if ($this->normalise($first[$field] ?? null) !== $this->normalise($second[$field] ?? null)) return false;
        }
        return true;
    }

    private function normalise(mixed $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $value) ?? ''));
    }

    private function clearDefault(User $user, string $party, ?int $exceptId = null): void
    {
        $query = UserAddress::query()
            ->where('user_id', $user->id)
            ->where('type', UserAddress::TYPE_BILLING)
            ->where('party', $party)
            ->where('is_default', true);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update(['is_default' => false]);
    }
}
