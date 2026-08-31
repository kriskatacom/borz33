<?php

declare(strict_types=1);

namespace App\Services\Shipping;

final class EcontShippingService
{
    /** @var array<string,mixed> */
    private array $config;
    private ?\Closure $transport;

    public function __construct(?array $config = null, ?callable $transport = null)
    {
        $this->config = $config ?? require dirname(__DIR__, 4) . '/config/econt.php';
        $this->transport = $transport === null ? null : \Closure::fromCallable($transport);
        $this->assertConfiguration();
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
        $response = $this->request(['mode' => 'calculate', 'label' => $this->withoutNulls($label)]);
        $calculated = $response['label'] ?? null;
        if (!is_array($calculated) || !isset($calculated['totalPrice'])) throw new \RuntimeException('Econt не върна изчислена цена за доставката.');
        $currency = strtoupper((string) ($calculated['currency'] ?? $this->config['currency']));
        if ($currency !== strtoupper((string) $this->config['currency'])) throw new \RuntimeException('Econt върна цена в неочаквана валута.');
        $carrierAmount = round((float) $calculated['totalPrice'], 2);
        return ['amount' => $payer === 'receiver' ? $carrierAmount : 0.0, 'carrier_amount' => $carrierAmount, 'currency' => $currency, 'environment' => (string) $this->config['environment'], 'delivery_method' => $method, 'shipping_payer' => $payer, 'weight_kg' => $weight, 'order_value' => $value, 'cod_amount' => $cod, 'expected_delivery_date' => isset($calculated['expectedDeliveryDate']) ? (string) $calculated['expectedDeliveryDate'] : null, 'calculated_at' => gmdate('c')];
    }

    public function officeLocatorUrl(): string { return (string) $this->config['office_locator_url']; }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function request(array $payload): array
    {
        if ($this->transport !== null) { $result = ($this->transport)($payload, $this->config); if (!is_array($result)) throw new \RuntimeException('Невалиден тестов отговор от Econt.'); return $result; }
        $url = $this->config['api_base_url'] . '/' . $this->config['calculate_path'];
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $curl = curl_init($url); if ($curl === false) throw new \RuntimeException('Econt клиентът не може да бъде стартиран.');
        curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPAUTH => CURLAUTH_BASIC, CURLOPT_USERPWD => $this->config['username'] . ':' . $this->config['password'], CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'], CURLOPT_POSTFIELDS => $body, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => $this->config['timeout_seconds'], CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2]);
        $raw = curl_exec($curl); $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE); $error = curl_error($curl); curl_close($curl);
        if ($raw === false) throw new \RuntimeException('Връзката с тестовата среда на Econt е неуспешна' . ($error !== '' ? ': ' . $error : '.'));
        $decoded = json_decode((string) $raw, true); if (!is_array($decoded)) throw new \RuntimeException('Тестовата среда на Econt върна невалиден отговор.');
        if ($status < 200 || $status >= 300) throw new \RuntimeException('Econt: ' . $this->flattenError($decoded)); return $decoded;
    }

    private function assertConfiguration(): void
    {
        foreach (['environment','api_base_url','calculate_path','office_locator_url','username','password','currency'] as $key) if (trim((string) ($this->config[$key] ?? '')) === '') throw new \RuntimeException("Липсва Econt настройка: {$key}.");
        foreach (['name','agent','phone','office_code','city','post_code'] as $key) if (trim((string) ($this->config['sender'][$key] ?? '')) === '') throw new \RuntimeException("Липсва Econt настройка за подател: {$key}.");
        $api = parse_url((string) $this->config['api_base_url']); $locator = parse_url((string) $this->config['office_locator_url']);
        if (($api['scheme'] ?? '') !== 'https' || ($locator['scheme'] ?? '') !== 'https') throw new \RuntimeException('Econt URL адресите трябва да използват HTTPS.');
        if ($this->config['environment'] === 'demo' && strtolower((string) ($api['host'] ?? '')) !== 'demo.econt.com') throw new \RuntimeException('Demo режимът допуска само demo.econt.com.');
    }
    /** @param array<string,mixed> $value @return array<string,mixed> */
    private function withoutNulls(array $value): array { foreach ($value as $key => $item) { if ($item === null) unset($value[$key]); elseif (is_array($item)) $value[$key] = $this->withoutNulls($item); } return $value; }
    /** @param array<string,mixed> $error */
    private function flattenError(array $error): string { $parts = []; if (isset($error['message'])) $parts[] = trim((string) $error['message']); foreach (($error['innerErrors'] ?? []) as $inner) if (is_array($inner)) $parts[] = $this->flattenError($inner); return implode(': ', array_filter($parts)) ?: 'неуспешно изчисляване на доставката.'; }
}
