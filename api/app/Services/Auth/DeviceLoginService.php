<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Request;
use App\Exceptions\AuthException;
use App\Models\DeviceLoginCode;
use App\Models\User;
use App\Services\Mail\MailerInterface;
use App\Services\Mail\MailService;
use App\Services\Company\CompanyProfile;
use Illuminate\Support\Carbon;

class DeviceLoginService
{
    /** @var array<string, mixed> */
    private array $config;

    public function __construct(
        private readonly ?MailerInterface $mailer = null
    ) {
        $this->config = require dirname(__DIR__, 4) . '/config/auth.php';
    }

    public function challenge(User $user, string $deviceUuid, ?string $deviceName = null): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $minutes = $this->ttlMinutes();

        DeviceLoginCode::query()->create([
            'user_id' => $user->id,
            'device_uuid' => $deviceUuid,
            'device_name' => $deviceName,
            'user_agent' => Request::userAgent(),
            'ip_address' => Request::ip(),
            'code_hash' => $this->hashCode($user->email, $deviceUuid, $code),
            'attempts' => 0,
            'expires_at' => Carbon::now()->addMinutes($minutes),
        ]);

        $this->send($user, $code, $minutes);
    }

    public function verify(User $user, string $deviceUuid, string $code): void
    {
        /** @var DeviceLoginCode|null $record */
        $record = DeviceLoginCode::query()
            ->where('user_id', $user->id)
            ->where('device_uuid', $deviceUuid)
            ->whereNull('verified_at')
            ->orderByDesc('id')
            ->first();

        if ($record === null) {
            throw new AuthException('Невалиден код за устройство.');
        }

        if ($record->expires_at->lt(Carbon::now())) {
            throw new AuthException('Кодът е изтекъл. Поискайте нов.');
        }

        if ($record->attempts >= $this->maxAttempts()) {
            throw new AuthException('Кодът е блокиран след твърде много опити. Поискайте нов.');
        }

        $record->increment('attempts');

        if (!hash_equals((string) $record->code_hash, $this->hashCode($user->email, $deviceUuid, $code))) {
            throw new AuthException('Невалиден код за устройство.');
        }

        $record->forceFill([
            'verified_at' => Carbon::now(),
        ])->save();
    }

    public function resend(User $user, string $deviceUuid, ?string $deviceName = null): void
    {
        DeviceLoginCode::query()
            ->where('user_id', $user->id)
            ->where('device_uuid', $deviceUuid)
            ->whereNull('verified_at')
            ->delete();

        $this->challenge($user, $deviceUuid, $deviceName);
    }

    private function send(User $user, string $code, int $minutes): void
    {
        $subject = 'Код за вход от ново устройство';
        $company = CompanyProfile::get();
        $vatLine = $company['vat'] !== '' ? 'ДДС № ' . $company['vat'] . "\n" : '';

        $text = implode("\n", [
            'Здравейте, ' . $user->first_name . ',',
            '',
            'Някой се опитва да влезе в профила Ви от ново устройство.',
            'Код за потвърждение: ' . $code,
            'Кодът е валиден ' . $minutes . ' минути.',
            '',
            'Ако това не сте Вие, сменете паролата си.',
            '',
            $company['legal_name'],
            'ЕИК ' . $company['eik'],
            $vatLine . $company['address'] . ', ' . $company['postal_code'] . ' ' . $company['city'] . ', ' . $company['country'],
            $company['email'],
            $company['website'],
        ]);

        $this->mailer()->sendTemplate(
            $user->email,
            $subject,
            'device-login',
            [
                'title' => $subject,
                'preheader' => 'Код за вход от ново устройство: ' . $code,
                'first_name' => $user->first_name,
                'code' => $code,
                'expires_minutes' => $minutes,
            ],
            $text
        );
    }

    private function mailer(): MailerInterface
    {
        return $this->mailer ?? new MailService();
    }

    private function hashCode(string $email, string $deviceUuid, string $code): string
    {
        return hash('sha256', strtolower($email) . ':' . $deviceUuid . ':' . $code);
    }

    private function ttlMinutes(): int
    {
        return max(1, (int) $this->config['device_code_ttl_minutes']);
    }

    private function maxAttempts(): int
    {
        return max(1, (int) $this->config['device_code_max_attempts']);
    }
}
