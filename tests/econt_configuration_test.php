<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/bootstrap/eloquent.php';
require dirname(__DIR__) . '/api/app/Core/Autoloader.php';

use App\Models\SiteSetting;
use App\Services\Security\CredentialCipher;
use App\Services\Shipping\EcontApiClient;
use App\Services\Shipping\EcontConfigurationService;

putenv('APP_ENCRYPTION_KEY=base64:' . base64_encode(str_repeat('t', 32)));

$base = [
    'default_environment' => 'demo',
    'calculate_path' => 'Shipments/LabelService.createLabel.json',
    'connection_test_path' => 'Profile/ProfileService.getClientProfiles.json',
    'timeout_seconds' => 8,
    'currency' => 'EUR',
    'tracking_url' => 'https://ee.econt.com/load_direct.php',
    'environments' => [
        'demo' => ['api_base_url' => 'https://demo.econt.test/services', 'office_locator_url' => 'https://demo-locator.econt.test', 'username' => 'demo', 'password' => 'demo'],
        'production' => ['api_base_url' => 'https://production.econt.test/services', 'office_locator_url' => 'https://locator.econt.test'],
    ],
    'sender' => ['name' => 'Store', 'agent' => 'Agent', 'phone' => '0888000000', 'office_code' => '1000', 'city' => 'София', 'post_code' => '1000'],
];

$assert = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};
$expectFailure = static function (callable $callback, string $message) use ($assert): void {
    try { $callback(); } catch (RuntimeException) { return; }
    $assert(false, $message);
};

$service = new EcontConfigurationService($base, new CredentialCipher());
$settings = new SiteSetting(['econt_environment' => 'demo']);
$demo = $service->resolve($settings);
$assert($demo['environment'] === 'demo' && $demo['api_base_url'] === 'https://demo.econt.test/services', 'Demo endpoint selection failed.');

$settings->econt_environment = 'production';
$settings->econt_production_username = 'company';
$settings->econt_production_password = (new CredentialCipher())->encrypt('secret');
$settings->econt_production_verified_at = '2026-08-31 12:00:00';
$production = $service->resolve($settings);
$assert($production['environment'] === 'production' && $production['api_base_url'] === 'https://production.econt.test/services', 'Production endpoint selection failed.');
$assert($production['username'] === 'company' && $production['password'] === 'secret', 'Production credential decryption failed.');

$settings->econt_environment = 'demo';
$assert($service->resolve($settings)['api_base_url'] === 'https://demo.econt.test/services', 'Switch back to Demo failed.');
$settings->econt_environment = 'production';
$settings->econt_production_verified_at = null;
$expectFailure(static fn () => $service->resolve($settings), 'Unverified Production credentials were not blocked.');

$client = new EcontApiClient($production, static function (): array { throw new RuntimeException('Unauthorized'); });
$expectFailure(static fn () => $client->testConnection(), 'Invalid credentials test did not fail.');

echo "Econt configuration tests: OK\n";
