<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Exceptions\ValidationException;
use App\Resources\ProductImageResource;
use App\Services\Products\ProductAdminService;
use App\Services\Products\ProductImageService;
use App\Validation\ProductImageValidator;

class ProductImagesController extends Controller
{
    public function __construct(
        private readonly ProductAdminService $products = new ProductAdminService(),
        private readonly ProductImageService $images = new ProductImageService(),
        private readonly ProductImageValidator $validator = new ProductImageValidator()
    ) {
    }

    public function storeFront(string $id): never
    {
        $product = $this->products->find($this->id($id));
        $file = Request::file('image');

        if ($file === null) {
            throw new ValidationException(['image' => ['Изберете изображение.']]);
        }

        $image = $this->images->storeFront($product, $file, $this->alt());

        $this->created(['image' => ProductImageResource::toArray($image)], 'Предното изображение е записано.');
    }

    public function storeGallery(string $id): never
    {
        $product = $this->products->find($this->id($id));
        $files = Request::files('images');

        if ($files === []) {
            $single = Request::file('image');
            $files = $single === null ? [] : [$single];
        }

        if ($files === []) {
            throw new ValidationException(['image' => ['Изберете поне едно изображение.']]);
        }

        $stored = [];

        foreach ($files as $file) {
            $stored[] = ProductImageResource::toArray($this->images->storeGallery($product, $file, $this->alt()));
        }

        $this->created(['images' => $stored], count($stored) === 1 ? 'Изображението е добавено.' : 'Изображенията са добавени.');
    }

    public function update(string $id, string $imageId): never
    {
        $product = $this->products->find($this->id($id));
        $image = $this->images->findForProduct($product, $this->id($imageId));
        $payload = $this->validator->validateMeta(Request::input());
        $image = $this->images->updateMeta($image, $payload);

        $this->ok(['image' => ProductImageResource::toArray($image)], 'Изображението е обновено.');
    }

    public function makeFront(string $id, string $imageId): never
    {
        $product = $this->products->find($this->id($id));
        $image = $this->images->makeFront($this->images->findForProduct($product, $this->id($imageId)));

        $this->ok(['image' => ProductImageResource::toArray($image)], 'Изображението е зададено като предно.');
    }

    public function destroy(string $id, string $imageId): never
    {
        $product = $this->products->find($this->id($id));
        $this->images->deleteImage($this->images->findForProduct($product, $this->id($imageId)));

        $this->ok([], 'Изображението е изтрито.');
    }

    private function alt(): ?string
    {
        $alt = Request::input('alt');

        return is_string($alt) ? $alt : null;
    }

    private function id(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            $this->error('Записът не е намерен.', 404);
        }

        return (int) $id;
    }
}
