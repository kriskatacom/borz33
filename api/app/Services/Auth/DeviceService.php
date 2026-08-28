<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Request;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Carbon;

class DeviceService
{
    public function trust(User $user, string $deviceUuid, ?string $deviceName = null): UserDevice
    {
        $now = Carbon::now();

        /** @var UserDevice $device */
        $device = UserDevice::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'device_uuid' => $deviceUuid,
            ],
            [
                'device_name' => $deviceName,
                'user_agent' => Request::userAgent(),
                'ip_address' => Request::ip(),
                'is_trusted' => true,
                'trusted_at' => $now,
                'last_seen_at' => $now,
                'is_active' => true,
            ]
        );

        return $device;
    }

    public function findTrusted(User $user, string $deviceUuid): ?UserDevice
    {
        /** @var UserDevice|null $device */
        $device = UserDevice::query()
            ->where('user_id', $user->id)
            ->where('device_uuid', $deviceUuid)
            ->where('is_trusted', true)
            ->where('is_active', true)
            ->first();

        return $device;
    }

    public function hasTrustedDevice(User $user): bool
    {
        return UserDevice::query()
            ->where('user_id', $user->id)
            ->where('is_trusted', true)
            ->where('is_active', true)
            ->exists();
    }

    public function touch(UserDevice $device): void
    {
        $device->forceFill([
            'last_seen_at' => Carbon::now(),
            'user_agent' => Request::userAgent(),
            'ip_address' => Request::ip(),
        ])->save();
    }
}
