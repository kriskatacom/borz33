<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/bootstrap/eloquent.php';
require dirname(__DIR__) . '/api/app/Core/Autoloader.php';

use App\Models\SiteSetting;
use App\Services\Mail\EmailRenderer;
use App\Services\Company\CompanyProfile;
use Illuminate\Database\Capsule\Manager as DB;

$assert = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};

$fields = ['company_name', 'company_legal_name', 'company_eik', 'company_vat', 'company_mol', 'company_address', 'company_city', 'company_postal_code', 'company_country', 'company_phone', 'company_email', 'company_website', 'company_privacy_url', 'company_terms_url'];
$settings = SiteSetting::query()->firstOrCreate([]);

DB::connection()->beginTransaction();
try {
    $settings->forceFill([
        'company_name' => 'Тестова марка',
        'company_legal_name' => 'Тестова фирма ЕООД',
        'company_eik' => '123456789',
        'company_vat' => 'BG123456789',
        'company_mol' => 'Тестов Управител',
        'company_address' => 'Тестова улица 1',
        'company_city' => 'София',
        'company_postal_code' => '1000',
        'company_country' => 'България',
        'company_phone' => '+35920000000',
        'company_email' => 'test@example.com',
        'company_website' => 'https://example.com',
        'company_privacy_url' => 'https://example.com/privacy',
        'company_terms_url' => 'https://example.com/terms',
    ])->save();

    $company = CompanyProfile::get();
    $assert($company['legal_name'] === 'Тестова фирма ЕООД', 'Email профилът не използва юридическото име от настройките.');
    $assert($company['email'] === 'test@example.com', 'Email профилът не използва имейла от настройките.');

    $html = (new EmailRenderer())->render('verify-registration', [
        'title' => 'Тест', 'preheader' => 'Тест', 'first_name' => 'Иван', 'code' => '123456', 'expires_minutes' => 15,
    ]);
    $assert(str_contains($html, 'Тестова фирма ЕООД') && str_contains($html, '123456789') && str_contains($html, 'Тестов Управител'), 'Фирмените данни не се появяват в HTML email шаблона.');

    DB::connection()->rollBack();
    echo "Company profile email test passed.\n";
} catch (Throwable $exception) {
    DB::connection()->rollBack();
    throw $exception;
}
