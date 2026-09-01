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
        $this->ok(['suggestion' => $this->ai->generate($images)], 'AI предложенията са готови. Прегледайте ги преди запис.');
    }
}
