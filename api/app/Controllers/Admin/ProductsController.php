<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Resources\ProductResource;
use App\Services\Products\ProductAdminService;
use App\Validation\ProductValidator;

class ProductsController extends Controller
{
    public function __construct(
        private readonly ProductAdminService $products = new ProductAdminService(),
        private readonly ProductValidator $validator = new ProductValidator()
    ) {
    }

    public function index(): never
    {
        $this->ok($this->products->paginate(Request::query()), 'Списък с продукти.');
    }

    public function show(string $id): never
    {
        $product = $this->products->find($this->id($id), true);

        $this->ok(['product' => ProductResource::toAdminArray($product)]);
    }

    public function store(): never
    {
        $payload = $this->validator->validate(Request::input());
        $product = $this->products->create($payload);

        $this->created(['product' => ProductResource::toAdminArray($product)], 'Продуктът е създаден.');
    }

    public function update(string $id): never
    {
        $product = $this->products->find($this->id($id));
        $payload = $this->validator->validate(Request::input(), $product->id);
        $product = $this->products->update($product, $payload);

        $this->ok(['product' => ProductResource::toAdminArray($product)], 'Продуктът е обновен.');
    }

    public function destroy(string $id): never
    {
        $this->products->delete($this->products->find($this->id($id)), Request::wantsTrue('purge_images'));

        $this->ok([], 'Продуктът е изтрит.');
    }

    public function restore(string $id): never
    {
        $product = $this->products->restore($this->id($id));

        $this->ok(['product' => ProductResource::toAdminArray($product)], 'Продуктът е възстановен.');
    }

    public function sharePersonalization(string $id): never
    {
        $product = $this->products->find($this->id($id));
        $payload = $this->validator->validate(Request::input(), $product->id);
        $result = $this->products->sharePersonalization($product, $payload);

        $this->ok(
            ['product' => ProductResource::toAdminArray($result['product']), 'updated_count' => $result['updated_count']],
            'Персонализацията е запазена като настройка по подразбиране.'
        );
    }

    private function id(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            $this->error('Продуктът не е намерен.', 404);
        }

        return (int) $id;
    }
}
