<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Exceptions\AuthException;
use App\Models\LoginAttempt;
use Illuminate\Support\Carbon;

class LoginAttemptService
{
    /** @var array<string, mixed> */
    private array $config;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 4) . '/config/auth.php';
    }

    public function assertNotLocked(string $email, string $ip): void
    {
        $since = Carbon::now()->subMinutes($this->lockoutMinutes());

        $emailFails = LoginAttempt::query()
            ->where('email', $email)
            ->where('successful', false)
            ->where('created_at', '>=', $since)
            ->count();

        if ($emailFails >= $this->maxAttempts()) {
            throw new AuthException(
                'Твърде много неуспешни опити за вход. Опитайте отново след ' . $this->lockoutMinutes() . ' минути.',
                429,
                $this->lockoutMinutes() * 60
            );
        }

        $ipFails = LoginAttempt::query()
            ->where('ip_address', $ip)
            ->where('successful', false)
            ->where('created_at', '>=', $since)
            ->count();

        if ($ipFails >= $this->ipMaxAttempts()) {
            throw new AuthException(
                'Твърде много неуспешни опити за вход от този адрес. Опитайте отново по-късно.',
                429,
                $this->lockoutMinutes() * 60
            );
        }
    }

    public function record(string $email, string $ip, bool $successful): void
    {
        LoginAttempt::query()->create([
            'email' => $email,
            'ip_address' => $ip,
            'successful' => $successful,
            'created_at' => Carbon::now(),
        ]);
    }

    private function maxAttempts(): int
    {
        return max(1, (int) $this->config['max_attempts']);
    }

    private function ipMaxAttempts(): int
    {
        return max($this->maxAttempts(), (int) $this->config['ip_max_attempts']);
    }

    private function lockoutMinutes(): int
    {
        return max(1, (int) $this->config['lockout_minutes']);
    }
}
