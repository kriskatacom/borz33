<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Exceptions\ValidationException;
use App\Resources\ProductImageResource;
use App\Services\Media\MediaService;
use App\Services\Products\ProductAdminService;
use App\Services\Products\ProductImageService;
use App\Validation\ProductImageValidator;

class ProductImagesController extends Controller
{
    public function __construct(
        private readonly ProductAdminService $products = new ProductAdminService(),
        private readonly ProductImageService $images = new ProductImageService(),
        private readonly ProductImageValidator $validator = new ProductImageValidator(),
        private readonly MediaService $media = new MediaService()
    ) {
    }

    public function storeFront(string $id): never
    {
        $product = $this->products->find($this->id($id));
        $mediaId = $this->firstMediaId();

        if ($mediaId !== null) {
            $image = $this->images->attachFront($product, $this->media->requireRasterImage($mediaId), $this->alt());
            $this->created(['image' => ProductImageResource::toArray($image)], 'Предното изображение е записано.');
        }

        $file = Request::file('image');

        if ($file === null) {
            throw new ValidationException(['image' => ['Изберете изображение или файл от медията.']]);
        }

        $image = $this->images->storeFront($product, $file, $this->alt());

        $this->created(['image' => ProductImageResource::toArray($image)], 'Предното изображение е записано.');
    }

    public function storeGallery(string $id): never
    {
        $product = $this->products->find($this->id($id));
        $mediaIds = $this->mediaIds();
        $files = Request::files('images');

        if ($files === []) {
            $single = Request::file('image');
            $files = $single === null ? [] : [$single];
        }

        if ($mediaIds === [] && $files === []) {
            throw new ValidationException(['image' => ['Изберете поне едно изображение или файл от медията.']]);
        }

        $stored = [];

        foreach ($mediaIds as $mediaId) {
            $stored[] = ProductImageResource::toArray($this->images->attachGallery($product, $this->media->requireRasterImage($mediaId), $this->alt()));
        }

        foreach ($files as $file) {
            $stored[] = ProductImageResource::toArray($this->images->storeGallery($product, $file, $this->alt()));
        }

        $this->created(['images' => $stored], count($stored) === 1 ? 'Изображението е добавено.' : 'Изображенията са добавени.');
    }

    public function storeVariant(string $id, string $variantId): never
    {
        $product = $this->products->find($this->id($id));
        $variant = $this->products->findVariant($product, $this->id($variantId));
        $mediaId = $this->firstMediaId();

        if ($mediaId !== null) {
            $image = $this->images->attachVariant($product, $variant, $this->media->requireRasterImage($mediaId), $this->alt());
            $this->created(['image' => ProductImageResource::toArray($image)], 'Изображението на варианта е записано.');
        }

        $file = Request::file('image');

        if ($file === null) {
            throw new ValidationException(['image' => ['Изберете изображение или файл от медията.']]);
        }

        $image = $this->images->storeVariant($product, $variant, $file, $this->alt());

        $this->created(['image' => ProductImageResource::toArray($image)], 'Изображението на варианта е записано.');
    }

    public function destroyVariant(string $id, string $variantId): never
    {
        $product = $this->products->find($this->id($id));
        $variant = $this->products->findVariant($product, $this->id($variantId));
        $image = $variant->image()->first();

        if ($image === null) {
            $this->error('Изображението не е намерено.', 404);
        }

        $this->images->deleteImage($image);

        $this->ok([], 'Изображението на варианта е изтрито.');
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

    private function firstMediaId(): ?int
    {
        return $this->mediaIds()[0] ?? null;
    }

    /** @return list<int> */
    private function mediaIds(): array
    {
        $raw = Request::input('media_ids', Request::input('media_id'));

        if (!is_array($raw)) {
            $raw = $raw === null || $raw === '' ? [] : [$raw];
        }

        $ids = [];

        foreach ($raw as $value) {
            if (is_int($value) && $value > 0) {
                $ids[] = $value;
            } elseif (is_string($value) && ctype_digit($value) && (int) $value > 0) {
                $ids[] = (int) $value;
            }
        }

        return array_values(array_unique($ids));
    }

    private function id(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            $this->error('Записът не е намерен.', 404);
        }

        return (int) $id;
    }
}
