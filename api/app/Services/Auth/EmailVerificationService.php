<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Exceptions\AuthException;
use App\Models\EmailVerificationToken;
use App\Models\User;
use App\Services\Mail\MailerInterface;
use App\Services\Mail\MailService;
use Illuminate\Support\Carbon;

class EmailVerificationService
{
    /** @var array<string, mixed> */
    private array $mailConfig;

    public function __construct(
        private readonly MailerInterface $mailer = new MailService()
    ) {
        $this->mailConfig = require dirname(__DIR__, 4) . '/config/mail.php';
    }

    public function issueAndSend(User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $code = $this->storeCode($user);
        $this->send($user, $code);
    }

    public function storeCode(User $user): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationToken::query()->updateOrCreate(
            ['email' => $user->email],
            [
                'token' => $this->hashCode($user->email, $code),
                'created_at' => Carbon::now(),
            ]
        );

        return $code;
    }

    public function send(User $user, string $code): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $minutes = $this->ttlMinutes();
        $subject = 'Код за потвърждение на регистрацията';

        $this->mailer->sendTemplate(
            $user->email,
            $subject,
            'verify-registration',
            [
                'title' => $subject,
                'preheader' => 'Вашият код за потвърждение е ' . $code . '.',
                'first_name' => $user->first_name,
                'code' => $code,
                'expires_minutes' => $minutes,
            ],
            $this->plainText($user->first_name, $code, $minutes)
        );
    }

    public function verify(string $email, string $code): User
    {
        $user = User::query()->where('email', strtolower(trim($email)))->first();

        if ($user === null || $user->isAdmin()) {
            throw new AuthException('Невалиден код за потвърждение.');
        }

        if ($user->hasVerifiedEmail()) {
            throw new AuthException('Имейлът вече е потвърден.');
        }

        $record = EmailVerificationToken::query()->where('email', $user->email)->first();

        if ($record === null || $record->created_at === null) {
            throw new AuthException('Невалиден код за потвърждение.');
        }

        if ($record->created_at->lt(Carbon::now()->subMinutes($this->ttlMinutes()))) {
            throw new AuthException('Кодът е изтекъл. Поискайте нов.');
        }

        if (!hash_equals((string) $record->token, $this->hashCode($user->email, $code))) {
            throw new AuthException('Невалиден код за потвърждение.');
        }

        $user->markEmailAsVerified();
        $record->delete();

        return $user->fresh() ?? $user;
    }

    public function resend(string $email): void
    {
        $user = User::query()->where('email', strtolower(trim($email)))->first();

        if ($user === null || $user->isAdmin() || $user->hasVerifiedEmail()) {
            return;
        }

        $this->issueAndSend($user);
    }

    private function hashCode(string $email, string $code): string
    {
        return hash('sha256', strtolower($email) . ':' . $code);
    }

    private function ttlMinutes(): int
    {
        $minutes = (int) ($this->mailConfig['verification_ttl_minutes'] ?? 15);

        return $minutes > 0 ? $minutes : 15;
    }

    private function plainText(string $firstName, string $code, int $minutes): string
    {
        $company = require dirname(__DIR__, 4) . '/config/company.php';
        $vatLine = $company['vat'] !== '' ? 'ДДС № ' . $company['vat'] . "\n" : '';

        return implode("\n", [
            'Здравейте, ' . $firstName . ',',
            '',
            'Вашият код за потвърждение на регистрацията е: ' . $code,
            'Кодът е валиден ' . $minutes . ' минути.',
            '',
            'Ако не сте създавали профил, игнорирайте това съобщение.',
            '',
            $company['legal_name'],
            'ЕИК ' . $company['eik'],
            $vatLine . $company['address'] . ', ' . $company['postal_code'] . ' ' . $company['city'] . ', ' . $company['country'],
            $company['email'] . ($company['phone'] !== '' ? ' · ' . $company['phone'] : ''),
            $company['website'],
            'Поверителност: ' . $company['privacy_url'],
            'Общи условия: ' . $company['terms_url'],
        ]);
    }
}
