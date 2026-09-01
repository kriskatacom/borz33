<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Services\Products\ProductAiService;

final class ProductAiController extends Controller
{
    public function __construct(
        private readonly ProductAiService $ai = new ProductAiService()
    ) {
    }

    public function generate(): never
    {
        $images = Request::files('images');
        $productId = (int) Request::input('product_id', 0);
        $rawIds = Request::input('image_ids', []);
        $imageIds = is_array($rawIds) ? array_map('intval', $rawIds) : [];
        $suggestion = $productId > 0
            ? $this->ai->generateForProduct($productId, $imageIds)
            : $this->ai->generate($images);

        $this->ok(['suggestion' => $suggestion], 'AI предложенията са готови. Прегледайте ги преди запис.');
    }
}
