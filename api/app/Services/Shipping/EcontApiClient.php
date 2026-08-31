<?php

declare(strict_types=1);

namespace App\Services\Shipping;

final class EcontApiClient
{
    private ?\Closure $transport;

    /** @param array<string,mixed> $config */
    public function __construct(private readonly array $config, ?callable $transport = null)
    {
        $this->transport = $transport === null ? null : \Closure::fromCallable($transport);
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    public function post(string $path, array $payload): array
    {
        if ($this->transport !== null) {
            $result = ($this->transport)($path, $payload, $this->config);
            if (!is_array($result)) throw new \RuntimeException('Невалиден тестов отговор от Econt.');
            return $result;
        }

        $url = $this->config['api_base_url'] . '/' . ltrim($path, '/');
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $curl = curl_init($url);
        if ($curl === false) throw new \RuntimeException('Econt клиентът не може да бъде стартиран.');

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->config['username'] . ':' . $this->config['password'],
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => $this->config['timeout_seconds'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $raw = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($raw === false) {
            error_log('Econt request failed [environment=' . $this->config['environment'] . ', path=' . $path . ', transport=curl].');
            throw new \RuntimeException('Връзката с Econt е неуспешна' . ($error !== '' ? ': ' . $error : '.'));
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) throw new \RuntimeException('Econt върна невалиден отговор.');

        if ($status < 200 || $status >= 300) {
            error_log('Econt request rejected [environment=' . $this->config['environment'] . ', path=' . $path . ', status=' . $status . '].');
            throw new \RuntimeException('Econt: ' . $this->flattenError($decoded));
        }

        return $decoded;
    }

    public function testConnection(): void
    {
        $this->post((string) $this->config['connection_test_path'], []);
    }

    /** @param array<string,mixed> $error */
    private function flattenError(array $error): string
    {
        $parts = [];
        if (isset($error['message'])) $parts[] = trim((string) $error['message']);
        foreach (($error['innerErrors'] ?? []) as $inner) if (is_array($inner)) $parts[] = $this->flattenError($inner);

        return implode(': ', array_filter($parts)) ?: 'заявката е отхвърлена.';
    }
}
