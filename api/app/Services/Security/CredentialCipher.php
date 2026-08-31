<?php

declare(strict_types=1);

namespace App\Services\Security;

final class CredentialCipher
{
    private const CIPHER = 'aes-256-gcm';

    public function encrypt(string $plainText): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $encrypted = openssl_encrypt($plainText, self::CIPHER, $this->key(), OPENSSL_RAW_DATA, $iv, $tag);

        if ($encrypted === false) {
            throw new \RuntimeException('Чувствителната настройка не можа да бъде криптирана.');
        }

        return 'v1:' . base64_encode($iv . $tag . $encrypted);
    }

    public function decrypt(?string $encrypted): string
    {
        if ($encrypted === null || $encrypted === '') {
            return '';
        }

        if (!str_starts_with($encrypted, 'v1:')) {
            throw new \RuntimeException('Запазените Econt данни са в невалиден формат.');
        }

        $payload = base64_decode(substr($encrypted, 3), true);

        if ($payload === false || strlen($payload) < 29) {
            throw new \RuntimeException('Запазените Econt данни не могат да бъдат разчетени.');
        }

        $plain = openssl_decrypt(substr($payload, 28), self::CIPHER, $this->key(), OPENSSL_RAW_DATA, substr($payload, 0, 12), substr($payload, 12, 16));

        if ($plain === false) {
            throw new \RuntimeException('Запазените Econt данни не могат да бъдат разчетени с текущия ключ.');
        }

        return $plain;
    }

    private function key(): string
    {
        $configured = trim((string) (getenv('APP_ENCRYPTION_KEY') ?: ''));
        $decoded = str_starts_with($configured, 'base64:') ? base64_decode(substr($configured, 7), true) : false;

        if ($decoded === false || strlen($decoded) !== 32) {
            throw new \RuntimeException('APP_ENCRYPTION_KEY трябва да бъде base64 кодиран 32-байтов ключ.');
        }

        return $decoded;
    }
}
