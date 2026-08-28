<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Exceptions\AuthException;
use App\Exceptions\ValidationException;
use App\Models\MediaFile;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\Media\MediaService;
use App\Validation\ProductImageValidator;

class ProductImageService
{
    public function __construct(
        private readonly ProductImageStorage $storage = new ProductImageStorage(),
        private readonly ProductImageValidator $validator = new ProductImageValidator(),
        private readonly MediaService $media = new MediaService()
    ) {
    }

    /** @param array<string, mixed> $file */
    public function storeFront(Product $product, array $file, ?string $alt = null): ProductImage
    {
        $this->validator->validateUpload($file);

        return $this->attachFront($product, $this->media->store($file), $alt);
    }

    public function attachFront(Product $product, MediaFile $media, ?string $alt = null): ProductImage
    {
        $this->assertRaster($media);
        $existing = $product->frontImage()->first();

        if ($existing !== null) {
            $this->releaseFile($existing);
            $existing->forceFill([
                ...$this->fromMedia($media, $alt, $existing->alt),
                'product_variant_id' => null,
                'sort_order' => 0,
            ])->save();

            return $existing->fresh() ?? $existing;
        }

        return $this->createImage($product, ProductImage::ROLE_FRONT, $media, $alt, 0);
    }

    /** @param array<string, mixed> $file */
    public function storeGallery(Product $product, array $file, ?string $alt = null): ProductImage
    {
        $this->validator->validateUpload($file);

        return $this->attachGallery($product, $this->media->store($file), $alt);
    }

    public function attachGallery(Product $product, MediaFile $media, ?string $alt = null): ProductImage
    {
        $this->assertRaster($media);
        $maxSort = (int) $product->galleryImages()->max('sort_order');

        return $this->createImage($product, ProductImage::ROLE_GALLERY, $media, $alt, $maxSort + 1);
    }

    /** @param array<string, mixed> $file */
    public function storeVariant(Product $product, ProductVariant $variant, array $file, ?string $alt = null): ProductImage
    {
        $this->validator->validateUpload($file);

        return $this->attachVariant($product, $variant, $this->media->store($file), $alt);
    }

    public function attachVariant(Product $product, ProductVariant $variant, MediaFile $media, ?string $alt = null): ProductImage
    {
        $this->assertRaster($media);
        $existing = ProductImage::query()
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variant->id)
            ->first();

        if ($existing !== null) {
            $this->releaseFile($existing);
            $existing->forceFill([
                ...$this->fromMedia($media, $alt, $existing->alt),
                'role' => ProductImage::ROLE_VARIANT,
                'sort_order' => 0,
            ])->save();

            return $existing->fresh() ?? $existing;
        }

        $image = new ProductImage();
        $image->forceFill([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'role' => ProductImage::ROLE_VARIANT,
            ...$this->fromMedia($media, $alt, null),
            'sort_order' => 0,
        ])->save();

        return $image;
    }

    /** @param array<int, mixed> $variantIds */
    public function deleteForVariantIds(array $variantIds): void
    {
        if ($variantIds === []) {
            return;
        }

        $images = ProductImage::query()->whereIn('product_variant_id', $variantIds)->get();

        foreach ($images as $image) {
            $this->deleteImage($image);
        }
    }

    /** @param array<string, mixed> $data */
    public function updateMeta(ProductImage $image, array $data): ProductImage
    {
        $payload = [];

        if (array_key_exists('alt', $data)) {
            $payload['alt'] = $this->nullableAlt(isset($data['alt']) ? (string) $data['alt'] : null);
        }

        if (array_key_exists('sort_order', $data)) {
            $payload['sort_order'] = (int) $data['sort_order'];
        }

        if ($payload !== []) {
            $image->forceFill($payload)->save();
        }

        return $image->fresh() ?? $image;
    }

    public function makeFront(ProductImage $image): ProductImage
    {
        if ($image->isVariant()) {
            throw new ValidationException(['image' => ['Снимката на вариант не може да е предна.']]);
        }

        if ($image->isFront()) {
            return $image;
        }

        $currentFront = ProductImage::query()
            ->where('product_id', $image->product_id)
            ->where('role', ProductImage::ROLE_FRONT)
            ->first();

        if ($currentFront !== null) {
            $currentFront->forceFill([
                'role' => ProductImage::ROLE_GALLERY,
                'sort_order' => 0,
            ])->save();
        }

        $image->forceFill([
            'product_variant_id' => null,
            'role' => ProductImage::ROLE_FRONT,
            'sort_order' => 0,
        ])->save();

        return $image->fresh() ?? $image;
    }

    public function deleteImage(ProductImage $image): void
    {
        $this->releaseFile($image);
        $image->delete();
    }

    public function purgeForProduct(Product $product): void
    {
        $images = ProductImage::query()->where('product_id', $product->id)->get();

        foreach ($images as $image) {
            $this->releaseFile($image);
            $image->delete();
        }

        $this->storage->deleteProductDirectory((int) $product->id);
    }

    public function findForProduct(Product $product, int $imageId): ProductImage
    {
        $image = ProductImage::query()
            ->where('product_id', $product->id)
            ->where('id', $imageId)
            ->first();

        if ($image === null) {
            throw new AuthException('Изображението не е намерено.', 404);
        }

        return $image;
    }

    private function createImage(Product $product, string $role, MediaFile $media, ?string $alt, int $sortOrder): ProductImage
    {
        $image = new ProductImage();
        $image->forceFill([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'role' => $role,
            ...$this->fromMedia($media, $alt, null),
            'sort_order' => $sortOrder,
        ])->save();

        return $image;
    }

    /** @return array<string, mixed> */
    private function fromMedia(MediaFile $media, ?string $alt, mixed $fallbackAlt): array
    {
        $resolvedAlt = $alt !== null ? $this->nullableAlt($alt) : (is_string($fallbackAlt) ? $this->nullableAlt($fallbackAlt) : $this->nullableAlt($media->alt));

        return [
            'media_file_id' => $media->id,
            'path' => $media->path,
            'original_name' => $media->original_name,
            'mime' => $media->mime,
            'size' => $media->size,
            'alt' => $resolvedAlt,
        ];
    }

    private function assertRaster(MediaFile $media): void
    {
        if (!isset(ProductImageStorage::MIME_EXTENSIONS[$media->mime])) {
            throw new ValidationException(['image' => ['Разрешени са JPEG, PNG и WebP.']]);
        }
    }

    private function releaseFile(ProductImage $image): void
    {
        if ($image->media_file_id) {
            return;
        }

        $this->storage->deleteFile($image->path);
    }

    private function nullableAlt(?string $alt): ?string
    {
        $value = trim((string) $alt);

        return $value === '' ? null : $value;
    }
}
