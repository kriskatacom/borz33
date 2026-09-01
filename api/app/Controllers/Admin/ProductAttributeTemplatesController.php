<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Models\Product;
use App\Services\Products\ProductAttributeTemplateService;
use App\Services\Products\ProductAdminService;
use App\Validation\ProductAttributeTemplateValidator;

class ProductAttributeTemplatesController extends Controller
{
    public function __construct(private readonly ProductAttributeTemplateService $templates = new ProductAttributeTemplateService(), private readonly ProductAttributeTemplateValidator $validator = new ProductAttributeTemplateValidator(), private readonly ProductAdminService $products = new ProductAdminService()) {}

    public function index(): never { $this->ok(['templates' => $this->templates->all()]); }
    public function store(): never { $this->created(['template' => $this->templates->create($this->validator->validate(Request::input()))], 'Шаблонът е създаден.'); }
    public function update(string $id): never { $this->ok(['template' => $this->templates->update($this->templates->find($this->id($id)), $this->validator->validate(Request::input(), true))], 'Шаблонът е обновен.'); }
    public function destroy(string $id): never { $this->templates->find($this->id($id))->delete(); $this->ok([], 'Шаблонът е изтрит.'); }
    public function apply(string $productId): never
    {
        $input = Request::input(); $sections = is_array($input['sections'] ?? null) ? array_map('strval', $input['sections']) : [];
        $product = $this->templates->apply($this->products->find($this->id($productId)), (int) ($input['template_id'] ?? 0), $sections);
        $this->ok(['product' => \App\Resources\ProductResource::toAdminArray($product)], 'Шаблонът е приложен. Добавени са само липсващите данни.');
    }
    private function id(string $id): int { if (!ctype_digit($id) || (int) $id < 1) $this->error('Записът не е намерен.', 404); return (int) $id; }
}
