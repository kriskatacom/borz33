<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Exceptions\AuthException;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Services\Mail\MailerInterface;
use App\Services\Mail\MailService;
use Illuminate\Support\Carbon;

class PasswordResetService
{
    /** @var array<string, mixed> */
    private array $config;

    public function __construct(
        private readonly PasswordHasher $passwordHasher = new PasswordHasher(),
        private readonly TokenService $tokenService = new TokenService(),
        private readonly MailerInterface $mailer = new MailService()
    ) {
        $this->config = require dirname(__DIR__, 4) . '/config/auth.php';
    }

    public function sendResetLink(string $email, bool $adminOnly = false): void
    {
        $email = strtolower(trim($email));
        $user = User::query()->where('email', $email)->first();

        if ($user === null || !$user->isActive() || ($adminOnly && !$user->isAdmin())) {
            return;
        }

        $existing = PasswordResetToken::query()->find($email);

        if ($existing?->created_at !== null && $existing->created_at->gt(Carbon::now()->subMinutes(2))) {
            return;
        }

        $plain = bin2hex(random_bytes(32));
        $minutes = $this->ttlMinutes();

        PasswordResetToken::query()->updateOrCreate(
            ['email' => $email],
            [
                'token' => hash('sha256', $plain),
                'created_at' => Carbon::now(),
            ]
        );

        $base = rtrim((string) ($adminOnly
            ? ($this->config['admin_public_url'] ?? 'http://localhost:3000')
            : ($this->config['public_url'] ?? 'http://localhost:3000')), '/');
        $url = $base . '/reset-password?' . http_build_query([
            'email' => $email,
            'token' => $plain,
        ]);

        $subject = $adminOnly ? 'Нова парола за админ панела' : 'Нова парола за профила Ви';
        $preheader = $adminOnly
            ? 'Линк за нова парола в админ панела на Borz33.'
            : 'Линк за нова парола в профила Ви в Borz33.';

        $this->mailer->sendTemplate(
            $user->email,
            $subject,
            'password-reset',
            [
                'title' => $subject,
                'preheader' => $preheader,
                'first_name' => $user->first_name,
                'reset_url' => $url,
                'expires_minutes' => $minutes,
            ],
            'Здравейте, ' . $user->first_name . ".\n\nНова парола: " . $url . "\nЛинкът е валиден " . $minutes . " минути."
        );
    }

    public function reset(array $data, bool $adminOnly = false): void
    {
        $email = strtolower(trim((string) $data['email']));
        $user = User::query()->where('email', $email)->first();
        $record = PasswordResetToken::query()->find($email);

        if (
            $user === null
            || !$user->isActive()
            || ($adminOnly && !$user->isAdmin())
            || $record === null
            || $record->created_at === null
            || $record->created_at->lt(Carbon::now()->subMinutes($this->ttlMinutes()))
            || !hash_equals((string) $record->token, hash('sha256', (string) $data['token']))
        ) {
            throw new AuthException('Линкът за нова парола е невалиден или изтекъл.');
        }

        $user->forceFill([
            'password' => $this->passwordHasher->hash((string) $data['password']),
        ])->save();

        $record->delete();
        $this->tokenService->revokeAllForUser($user);
    }

    private function ttlMinutes(): int
    {
        return max(5, (int) ($this->config['password_reset_ttl_minutes'] ?? 60));
    }
}
