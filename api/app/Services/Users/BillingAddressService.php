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

        if ($count >= self::MAX) {
            throw new ValidationException(['label' => ['Може да запишете най-много ' . self::MAX . ' адреса за фактуриране.']]);
        }

        return Capsule::connection()->transaction(function () use ($user, $data, $count): UserAddress {
            $makeDefault = (bool) ($data['is_default'] ?? false) || $count === 0;

            if ($makeDefault) {
                $this->clearDefault($user);
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
                $this->clearDefault($user, (int) $address->id);
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

            $next = $this->list($user)->first();

            if ($next !== null) {
                $next->forceFill(['is_default' => true])->save();
            }
        });
    }

    public function setDefault(User $user, UserAddress $address): UserAddress
    {
        return Capsule::connection()->transaction(function () use ($user, $address): UserAddress {
            $this->clearDefault($user);
            $address->forceFill(['is_default' => true])->save();

            return $address->refresh();
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

    private function clearDefault(User $user, ?int $exceptId = null): void
    {
        $query = UserAddress::query()
            ->where('user_id', $user->id)
            ->where('type', UserAddress::TYPE_BILLING)
            ->where('is_default', true);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update(['is_default' => false]);
    }
}
