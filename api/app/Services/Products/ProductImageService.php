<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Exceptions\AuthException;
use App\Models\Product;
use App\Models\ProductImage;
use App\Validation\ProductImageValidator;

class ProductImageService
{
    public function __construct(
        private readonly ProductImageStorage $storage = new ProductImageStorage(),
        private readonly ProductImageValidator $validator = new ProductImageValidator()
    ) {
    }

    /** @param array<string, mixed> $file */
    public function storeFront(Product $product, array $file, ?string $alt = null): ProductImage
    {
        $this->validator->validateUpload($file);
        $stored = $this->storage->store((int) $product->id, $file, ProductImage::ROLE_FRONT);
        $existing = $product->frontImage()->first();

        if ($existing !== null) {
            $this->storage->deleteFile($existing->path);
            $existing->forceFill([
                'path' => $stored['path'],
                'original_name' => $this->originalName($file),
                'mime' => $stored['mime'],
                'size' => (int) ($file['size'] ?? 0),
                'alt' => $alt !== null ? $this->nullableAlt($alt) : $existing->alt,
                'sort_order' => 0,
            ])->save();

            return $existing->fresh() ?? $existing;
        }

        $image = new ProductImage();
        $image->forceFill([
            'product_id' => $product->id,
            'role' => ProductImage::ROLE_FRONT,
            'path' => $stored['path'],
            'original_name' => $this->originalName($file),
            'mime' => $stored['mime'],
            'size' => (int) ($file['size'] ?? 0),
            'alt' => $this->nullableAlt($alt),
            'sort_order' => 0,
        ])->save();

        return $image;
    }

    /** @param array<string, mixed> $file */
    public function storeGallery(Product $product, array $file, ?string $alt = null): ProductImage
    {
        $this->validator->validateUpload($file);
        $stored = $this->storage->store((int) $product->id, $file, ProductImage::ROLE_GALLERY);
        $maxSort = (int) $product->galleryImages()->max('sort_order');

        $image = new ProductImage();
        $image->forceFill([
            'product_id' => $product->id,
            'role' => ProductImage::ROLE_GALLERY,
            'path' => $stored['path'],
            'original_name' => $this->originalName($file),
            'mime' => $stored['mime'],
            'size' => (int) ($file['size'] ?? 0),
            'alt' => $this->nullableAlt($alt),
            'sort_order' => $maxSort + 1,
        ])->save();

        return $image;
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
            'role' => ProductImage::ROLE_FRONT,
            'sort_order' => 0,
        ])->save();

        return $image->fresh() ?? $image;
    }

    public function deleteImage(ProductImage $image): void
    {
        $this->storage->deleteFile($image->path);
        $image->delete();
    }

    public function purgeForProduct(Product $product): void
    {
        $images = ProductImage::query()->where('product_id', $product->id)->get();

        foreach ($images as $image) {
            $this->storage->deleteFile($image->path);
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

    /** @param array<string, mixed> $file */
    private function originalName(array $file): string
    {
        $name = basename((string) ($file['name'] ?? 'image'));

        return $name !== '' ? substr($name, 0, 255) : 'image';
    }

    private function nullableAlt(?string $alt): ?string
    {
        $value = trim((string) $alt);

        return $value === '' ? null : $value;
    }
}
