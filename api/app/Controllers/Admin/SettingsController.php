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
        ], [], ['logo_media_file_id' => 'лого']);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors()->toArray());
        }

        $data = $validator->validated();
        $logoId = $data['logo_media_file_id'] ?? null;

        if ($logoId !== null) {
            $logo = MediaFile::query()->find((int) $logoId);
            if ($logo === null || !$logo->isImage()) {
                throw new ValidationException(['logo_media_file_id' => ['Логото трябва да бъде изображение.']]);
            }
        }

        $settings = $this->settings();
        $settings->logo_media_file_id = $logoId !== null ? (int) $logoId : null;
        $settings->save();

        $this->ok(['settings' => $this->resource($settings->fresh('logo') ?? $settings)], 'Логото е обновено.');
    }

    private function settings(): SiteSetting
    {
        return SiteSetting::query()->with('logo')->firstOrCreate([]);
    }

    /** @return array{logo_media_file_id: int|null, logo: array<string, mixed>|null} */
    private function resource(SiteSetting $settings): array
    {
        $settings->loadMissing('logo');

        return [
            'logo_media_file_id' => $settings->logo_media_file_id,
            'logo' => $settings->logo ? MediaFileResource::toArray($settings->logo) : null,
        ];
    }
}
