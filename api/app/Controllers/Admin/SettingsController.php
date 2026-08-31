<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\ValidatorFactory;
use App\Exceptions\ValidationException;
use App\Models\MediaFile;
use App\Models\SiteSetting;
use App\Resources\MediaFileResource;
use App\Services\Security\CredentialCipher;
use App\Services\Shipping\EcontApiClient;
use App\Services\Shipping\EcontConfigurationService;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function show(): never
    {
        $this->ok(['settings' => $this->resource($this->settings())], 'Настройки на сайта.');
    }

    public function update(): never
    {
        $validator = ValidatorFactory::make()->make(Request::input(), [
            'logo_media_file_id' => ['nullable', 'integer', 'min:1', Rule::exists('media_files', 'id')],
            'vat_enabled' => ['sometimes', 'boolean'],
            'free_shipping_threshold' => ['sometimes', 'numeric', 'min:0', 'max:999999.99'],
            'econt_environment' => ['sometimes', 'string', Rule::in(['demo', 'production'])],
            'econt_production_username' => ['sometimes', 'nullable', 'string', 'max:191'],
            'econt_production_password' => ['sometimes', 'nullable', 'string', 'min:4', 'max:191'],
        ], [], ['logo_media_file_id' => 'лого']);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors()->toArray());
        }

        $data = $validator->validated();
        $logoId = $data['logo_media_file_id'] ?? $this->settings()->logo_media_file_id;

        if ($logoId !== null) {
            $logo = MediaFile::query()->find((int) $logoId);
            if ($logo === null || !$logo->isImage()) {
                throw new ValidationException(['logo_media_file_id' => ['Логото трябва да бъде изображение.']]);
            }
        }

        $settings = $this->settings();
        $settings->logo_media_file_id = $logoId !== null ? (int) $logoId : null;
        if (array_key_exists('vat_enabled', $data)) $settings->vat_enabled = (bool) $data['vat_enabled'];
        if (array_key_exists('free_shipping_threshold', $data)) $settings->free_shipping_threshold = round((float) $data['free_shipping_threshold'], 2);
        $credentialsChanged = false;
        if (array_key_exists('econt_production_username', $data)) {
            $username = trim((string) ($data['econt_production_username'] ?? ''));
            $credentialsChanged = $username !== (string) $settings->econt_production_username;
            $settings->econt_production_username = $username !== '' ? $username : null;
        }
        if (!empty($data['econt_production_password'])) {
            try {
                $settings->econt_production_password = (new CredentialCipher())->encrypt((string) $data['econt_production_password']);
            } catch (\RuntimeException $exception) {
                throw new ValidationException(['econt_production_password' => [$exception->getMessage()]]);
            }
            $credentialsChanged = true;
        }
        if ($credentialsChanged) $settings->econt_production_verified_at = null;
        if (array_key_exists('econt_environment', $data)) {
            $environment = (string) $data['econt_environment'];
            if ($environment === 'production' && (trim((string) $settings->econt_production_username) === '' || trim((string) $settings->econt_production_password) === '')) {
                throw new ValidationException(['econt_environment' => ['Попълнете Production username и password преди превключване.']]);
            }
            $settings->econt_environment = $environment;
        }
        $settings->save();

        $this->ok(['settings' => $this->resource($settings->fresh('logo') ?? $settings)], 'Настройките са обновени.');
    }

    public function testEcont(): never
    {
        $validator = ValidatorFactory::make()->make(Request::input(), [
            'environment' => ['required', 'string', Rule::in(['demo', 'production'])],
            'username' => ['nullable', 'string', 'max:191'],
            'password' => ['nullable', 'string', 'max:191'],
        ]);
        if ($validator->fails()) throw new ValidationException($validator->errors()->toArray());

        $data = $validator->validated();
        $settings = $this->settings();
        $candidate = $settings->replicate();
        $candidate->econt_environment = (string) $data['environment'];

        try {
            if ($candidate->econt_environment === 'production') {
                $username = trim((string) ($data['username'] ?? $settings->econt_production_username ?? ''));
                $password = (string) ($data['password'] ?? '');
                $candidate->econt_production_username = $username;
                if ($password !== '') $candidate->econt_production_password = (new CredentialCipher())->encrypt($password);
            }

            $config = (new EcontConfigurationService())->resolve($candidate, false);
            (new EcontApiClient($config))->testConnection();

            if ($candidate->econt_environment === 'production') {
                $settings->econt_production_username = $candidate->econt_production_username;
                $settings->econt_production_password = $candidate->econt_production_password;
                $settings->econt_production_verified_at = date('Y-m-d H:i:s');
                $settings->save();
            }
        } catch (\Throwable $exception) {
            error_log('Econt connection test failed [environment=' . $candidate->econt_environment . ', reason=' . $exception::class . '].');
            $this->error('Econt връзката е неуспешна: ' . $exception->getMessage(), 422);
        }

        $this->ok(['settings' => $this->resource($settings->fresh('logo') ?? $settings)], 'Връзката с Econt е успешна.');
    }

    private function settings(): SiteSetting
    {
        return SiteSetting::query()->with('logo')->firstOrCreate([]);
    }

    /** @return array{logo_media_file_id: int|null, logo: array<string, mixed>|null, vat_enabled: bool} */
    private function resource(SiteSetting $settings): array
    {
        $settings->loadMissing('logo');

        return [
            'logo_media_file_id' => $settings->logo_media_file_id,
            'logo' => $settings->logo ? MediaFileResource::toArray($settings->logo) : null,
            'vat_enabled' => (bool) $settings->vat_enabled,
            'free_shipping_threshold' => (float) $settings->free_shipping_threshold,
            'econt' => [
                'environment' => in_array($settings->econt_environment, ['demo', 'production'], true) ? $settings->econt_environment : 'demo',
                'production_username' => (string) ($settings->econt_production_username ?? ''),
                'production_password_configured' => !empty($settings->econt_production_password),
                'production_password_masked' => !empty($settings->econt_production_password) ? '••••••••••••' : '',
                'production_verified_at' => $settings->econt_production_verified_at?->format(DATE_ATOM),
            ],
        ];
    }
}
