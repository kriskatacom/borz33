<?php

declare(strict_types=1);

namespace App\Services\Shipping;

final class EcontShippingService
{
    /** @var array<string,mixed> */
    private array $config;
    private EcontApiClient $client;

    public function __construct(?array $config = null, ?callable $transport = null)
    {
        $this->config = $config ?? (new EcontConfigurationService())->resolve();
        $this->client = new EcontApiClient($this->config, $transport);
    }

    /** @param array<string,mixed> $input @return array<string,mixed> */
    public function quote(array $input): array
    {
        $method = (string) ($input['delivery_method'] ?? '');
        if (!in_array($method, ['address', 'office', 'machine'], true)) throw new \InvalidArgumentException('Невалиден начин на доставка.');
        $payer = (string) ($input['shipping_payer'] ?? 'receiver');
        if (!in_array($payer, ['sender', 'receiver'], true)) throw new \InvalidArgumentException('Невалидна страна, плащаща доставката.');
        $weight = round(max(0.01, (float) ($input['weight_kg'] ?? 0)), 3);
        $value = round(max(0, (float) ($input['order_value'] ?? 0)), 2);
        $cod = round(max(0, (float) ($input['cod_amount'] ?? 0)), 2);
        $officeCode = trim((string) ($input['econt_office_code'] ?? ''));
        if ($method !== 'address' && $officeCode === '') throw new \InvalidArgumentException('Изберете офис или Еконтомат на Еконт.');

        $label = [
            'senderClient' => ['name' => $this->config['sender']['name'], 'phones' => [$this->config['sender']['phone']]],
            'senderAgent' => ['name' => $this->config['sender']['agent'], 'phones' => [$this->config['sender']['phone']]],
            'senderOfficeCode' => $this->config['sender']['office_code'],
            'receiverClient' => ['name' => trim((string) ($input['first_name'] ?? '') . ' ' . (string) ($input['last_name'] ?? '')), 'phones' => [(string) ($input['phone'] ?? '')]],
            'packCount' => 1, 'shipmentType' => 'pack', 'weight' => $weight,
            'sizeUnder60cm' => true, 'shipmentDescription' => 'Онлайн поръчка',
            'paymentSenderMethod' => $payer === 'sender' ? 'cash' : null,
            'paymentReceiverMethod' => $payer === 'receiver' ? 'cash' : null,
            'services' => ['declaredValueAmount' => $value, 'declaredValueCurrency' => $this->config['currency']],
        ];
        if ($cod > 0) { $label['services']['cdAmount'] = $cod; $label['services']['cdCurrency'] = $this->config['currency']; }
        if ($method === 'address') {
            $label['receiverAddress'] = ['city' => ['country' => ['code3' => 'BGR'], 'postCode' => (string) ($input['postal_code'] ?? ''), 'name' => (string) ($input['city'] ?? '')], 'other' => (string) ($input['address_line'] ?? '')];
        } else $label['receiverOfficeCode'] = $officeCode;

        // The only supported API mode is calculate. No label creation method exists in this client.
        $response = $this->client->post((string) $this->config['calculate_path'], ['mode' => 'calculate', 'label' => $this->withoutNulls($label)]);
        $calculated = $response['label'] ?? null;
        if (!is_array($calculated) || !isset($calculated['totalPrice'])) throw new \RuntimeException('Econt не върна изчислена цена за доставката.');
        $currency = strtoupper((string) ($calculated['currency'] ?? $this->config['currency']));
        if ($currency !== strtoupper((string) $this->config['currency'])) throw new \RuntimeException('Econt върна цена в неочаквана валута.');
        $carrierAmount = round((float) $calculated['totalPrice'], 2);
        return ['amount' => $payer === 'receiver' ? $carrierAmount : 0.0, 'carrier_amount' => $carrierAmount, 'currency' => $currency, 'environment' => (string) $this->config['environment'], 'delivery_method' => $method, 'shipping_payer' => $payer, 'weight_kg' => $weight, 'order_value' => $value, 'cod_amount' => $cod, 'expected_delivery_date' => isset($calculated['expectedDeliveryDate']) ? (string) $calculated['expectedDeliveryDate'] : null, 'calculated_at' => gmdate('c')];
    }

    public function officeLocatorUrl(): string { return (string) $this->config['office_locator_url']; }

    /** @param array<string,mixed> $value @return array<string,mixed> */
    private function withoutNulls(array $value): array { foreach ($value as $key => $item) { if ($item === null) unset($value[$key]); elseif (is_array($item)) $value[$key] = $this->withoutNulls($item); } return $value; }
}
