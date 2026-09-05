<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Exceptions\AuthException;
use App\Models\ProductOptionValue;
use App\Models\ProductColorSuggestion;
use App\Models\ProductVariantValue;
use App\Resources\ProductResource;
use App\Resources\ProductColorSuggestionResource;
use App\Services\Products\ProductAdminService;
use App\Services\Products\ProductAiService;
use Illuminate\Support\Str;

final class ProductColorAiController extends Controller
{
    public function __construct(
        private readonly ProductAdminService $products = new ProductAdminService(),
        private readonly ProductAiService $ai = new ProductAiService()
    ) {
    }

    public function index(string $id, string $variantId): never
    {
        $product = $this->products->find($this->id($id));
        $variant = $this->products->findVariant($product, $this->id($variantId));
        $items = ProductColorSuggestion::query()->where('product_variant_id', $variant->id)->latest('id')->limit(10)->get();
        $this->ok(['suggestions' => ProductColorSuggestionResource::collection($items)]);
    }

    public function generate(string $id, string $variantId): never
    {
        $product = $this->products->find($this->id($id));
        $variant = $this->products->findVariant($product, $this->id($variantId));
        $suggestion = $this->ai->suggestColorForVariant($variant);
        $this->created(['suggestion' => ProductColorSuggestionResource::toArray($suggestion)], 'Предложението за цвят е записано.');
    }

    public function apply(string $id, string $variantId, string $suggestionId): never
    {
        $product = $this->products->find($this->id($id));
        $variant = $this->products->findVariant($product, $this->id($variantId));
        $suggestion = ProductColorSuggestion::query()
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variant->id)
            ->find($this->id($suggestionId));
        if ($suggestion === null) throw new AuthException('Предложението за цвят не е намерено.', 404);

        $option = $product->options()
            ->where(static function ($query): void {
                $query->where('slug', 'color')->orWhereRaw("LOWER(name) LIKE '%цвят%'");
            })->first();
        if ($option === null) throw new AuthException('Към продукта няма добавена опция „Цвят“.', 422);

        $name = $this->capitalizeColorName(trim($suggestion->color_name_bg));
        $value = $option->values()->whereRaw('LOWER(name) = LOWER(?)', [$name])->first();
        if ($value === null) {
            $baseSlug = Str::slug($name, '-', 'bg') ?: 'color';
            $slug = $baseSlug;
            $suffix = 2;
            while ($option->values()->where('slug', $slug)->exists()) $slug = $baseSlug . '-' . $suffix++;
            $value = ProductOptionValue::query()->create([
                'product_option_id' => $option->id,
                'name' => $name,
                'slug' => $slug,
                'hex_color' => strtoupper($suggestion->color_hex),
                'sort_order' => (int) $option->values()->max('sort_order') + 1,
            ]);
        }

        ProductVariantValue::query()->where('product_variant_id', $variant->id)->where('product_option_id', $option->id)->delete();
        ProductVariantValue::query()->create([
            'product_variant_id' => $variant->id,
            'product_option_id' => $option->id,
            'product_option_value_id' => $value->id,
        ]);

        $this->ok([
            'product' => ProductResource::toAdminArray($product->fresh()),
            'suggestion' => ProductColorSuggestionResource::toArray($suggestion),
            'color_value' => [
                'id' => $value->id,
                'name' => $value->name,
                'slug' => $value->slug,
                'hex_color' => $value->hex_color,
            ],
        ], 'Цветът е приложен към варианта.');
    }

    public function delete(string $id, string $variantId, string $suggestionId): never
    {
        $product = $this->products->find($this->id($id));
        $variant = $this->products->findVariant($product, $this->id($variantId));
        $suggestion = ProductColorSuggestion::query()
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variant->id)
            ->find($this->id($suggestionId));

        if ($suggestion === null) {
            throw new AuthException('Предложението за цвят не е намерено.', 404);
        }

        $suggestion->delete();
        $this->ok([], 'Предложението за цвят е изтрито.');
    }

    private function capitalizeColorName(string $name): string
    {
        if ($name === '') return $name;
        return mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8')
            . mb_substr($name, 1, mb_strlen($name, 'UTF-8'), 'UTF-8');
    }

    private function id(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) $this->error('Записът не е намерен.', 404);
        return (int) $id;
    }
}
