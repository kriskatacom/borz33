<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Request;
use App\Models\ApiToken;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Carbon;

class TokenService
{
    /** @var array<string, mixed> */
    private array $config;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 4) . '/config/auth.php';
    }

    /** @return array{token: string, expires_at: string} */
    public function issue(User $user, ?UserDevice $device): array
    {
        $plain = bin2hex(random_bytes(32));
        $expiresAt = Carbon::now()->addDays(max(1, (int) $this->config['token_ttl_days']));

        ApiToken::query()->create([
            'user_id' => $user->id,
            'user_device_id' => $device?->id,
            'token_hash' => hash('sha256', $plain),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'last_used_at' => Carbon::now(),
            'expires_at' => $expiresAt,
        ]);

        return [
            'token' => $plain,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }
}
