<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Resources\BannerResource;
use App\Services\Banners\BannerAdminService;
use App\Validation\BannerValidator;

class BannersController extends Controller
{
    public function __construct(
        private readonly BannerAdminService $banners = new BannerAdminService(),
        private readonly BannerValidator $validator = new BannerValidator()
    ) {
    }

    public function index(): never
    {
        $this->ok($this->banners->paginate(Request::query()), 'Списък с банери.');
    }

    public function show(string $id): never
    {
        $banner = $this->banners->find($this->id($id), true);

        $this->ok(['banner' => BannerResource::toAdminArray($banner)]);
    }

    public function store(): never
    {
        $payload = $this->validator->validate(Request::input());
        $banner = $this->banners->create($payload);

        $this->created(['banner' => BannerResource::toAdminArray($banner)], 'Банерът е създаден.');
    }

    public function update(string $id): never
    {
        $banner = $this->banners->find($this->id($id));
        $payload = $this->validator->validate(Request::input(), $banner->id);
        $banner = $this->banners->update($banner, $payload);

        $this->ok(['banner' => BannerResource::toAdminArray($banner)], 'Банерът е обновен.');
    }

    public function destroy(string $id): never
    {
        $this->banners->delete($this->banners->find($this->id($id)));

        $this->ok([], 'Банерът е изтрит.');
    }

    public function restore(string $id): never
    {
        $banner = $this->banners->restore($this->id($id));

        $this->ok(['banner' => BannerResource::toAdminArray($banner)], 'Банерът е възстановен.');
    }

    private function id(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            $this->error('Банерът не е намерен.', 404);
        }

        return (int) $id;
    }
}
