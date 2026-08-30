<?php

declare(strict_types=1);

namespace App\Services\Shipping;

class EcontShippingService
{
    /** @var array<string, mixed> */
    private array $config;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 4) . '/config/econt.php';
    }

    /** @return array{amount: float, currency: string} */
    public function quote(string $deliveryMethod): array
    {
        if (!in_array($deliveryMethod, ['office', 'address'], true)) {
            throw new \InvalidArgumentException('Невалиден начин на доставка.');
        }

        $priceKey = $deliveryMethod === 'office' ? 'office_price' : 'address_price';

        return [
            'amount' => round((float) $this->config[$priceKey], 2),
            'currency' => (string) $this->config['currency'],
        ];
    }
}
