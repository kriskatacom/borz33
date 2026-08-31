<?php

declare(strict_types=1);

namespace App\Services\Shipping;

use App\Models\SiteSetting;
use App\Services\Security\CredentialCipher;

final class EcontConfigurationService
{
    /** @param array<string,mixed>|null $baseConfig */
    public function __construct(
        private readonly ?array $baseConfig = null,
        private readonly ?CredentialCipher $cipher = null,
    ) {
    }

    /** @return array<string,mixed> */
    public function resolve(?SiteSetting $settings = null, bool $requireVerifiedProduction = true): array
    {
        $base = $this->baseConfig ?? require dirname(__DIR__, 4) . '/config/econt.php';
        $settings ??= SiteSetting::query()->firstOrCreate([]);
        $environment = (string) ($settings->econt_environment ?: $base['default_environment'] ?? 'demo');

        if (!in_array($environment, ['demo', 'production'], true)) {
            throw new \RuntimeException('Избраната Econt среда е невалидна.');
        }

        $environmentConfig = $base['environments'][$environment] ?? null;

        if (!is_array($environmentConfig)) {
            throw new \RuntimeException('Липсва конфигурация за избраната Econt среда.');
        }

        if ($environment === 'production') {
            $environmentConfig['username'] = trim((string) $settings->econt_production_username);
            $environmentConfig['password'] = ($this->cipher ?? new CredentialCipher())->decrypt($settings->econt_production_password);

            if ($environmentConfig['username'] === '' || $environmentConfig['password'] === '') {
                throw new \RuntimeException('Production Econt данните липсват. Попълнете ги в Настройки → Econt.');
            }

            if ($requireVerifiedProduction && $settings->econt_production_verified_at === null) {
                throw new \RuntimeException('Production Econt връзката не е проверена. Използвайте „Тествай връзката“ в Настройки → Econt.');
            }
        }

        $resolved = array_merge($base, $environmentConfig, ['environment' => $environment]);
        unset($resolved['environments'], $resolved['default_environment']);
        $this->validateResolved($resolved);

        return $resolved;
    }

    /** @return array{environment:string,office_locator_url:string} */
    public function publicConfiguration(?SiteSetting $settings = null): array
    {
        $base = $this->baseConfig ?? require dirname(__DIR__, 4) . '/config/econt.php';
        $settings ??= SiteSetting::query()->firstOrCreate([]);
        $environment = (string) ($settings->econt_environment ?: $base['default_environment'] ?? 'demo');

        if (!in_array($environment, ['demo', 'production'], true)) {
            $environment = 'demo';
        }

        return [
            'environment' => $environment,
            'office_locator_url' => (string) ($base['environments'][$environment]['office_locator_url'] ?? ''),
        ];
    }

    public function trackingUrl(string $shipmentNumber): string
    {
        $base = $this->baseConfig ?? require dirname(__DIR__, 4) . '/config/econt.php';

        return (string) $base['tracking_url'] . '?lang=bg&shipment_num=' . rawurlencode($shipmentNumber) . '&target=EeActivityTraceParcell';
    }

    /** @param array<string,mixed> $config */
    private function validateResolved(array $config): void
    {
        foreach (['environment', 'api_base_url', 'calculate_path', 'connection_test_path', 'office_locator_url', 'username', 'password', 'currency'] as $key) {
            if (trim((string) ($config[$key] ?? '')) === '') {
                throw new \RuntimeException("Липсва Econt настройка: {$key}.");
            }
        }

        foreach (['name', 'agent', 'phone', 'office_code', 'city', 'post_code'] as $key) {
            if (trim((string) ($config['sender'][$key] ?? '')) === '') {
                throw new \RuntimeException("Липсва Econt настройка за подател: {$key}.");
            }
        }

        foreach (['api_base_url', 'office_locator_url'] as $urlKey) {
            if (parse_url((string) $config[$urlKey], PHP_URL_SCHEME) !== 'https') {
                throw new \RuntimeException('Econt URL адресите трябва да използват HTTPS.');
            }
        }
    }
}
