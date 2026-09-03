<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Exceptions\AuthException;
use App\Models\Category;
use App\Models\ProductImage;
use App\Validation\ProductImageValidator;

final class ProductAiService
{
    private const MAX_IMAGES = 8;

    /** @var array<string, mixed> */
    private array $config;

    public function __construct(
        private readonly ProductImageValidator $imageValidator = new ProductImageValidator()
    ) {
        $this->config = require dirname(__DIR__, 4) . '/config/openai.php';
    }

    /**
     * @param list<array<string, mixed>> $images
     * @return array<string, mixed>
     */
    public function generate(array $images): array
    {
        if (($this->config['admin_assistant_enabled'] ?? true) !== true) {
            throw new AuthException('AI помощта за продуктови данни е изключена от конфигурацията.', 503);
        }
        if ($images === []) {
            throw new AuthException('Добавете поне едно изображение на продукта.', 422);
        }

        if (count($images) > self::MAX_IMAGES) {
            throw new AuthException('За AI анализ може да изпратите най-много 8 изображения.', 422);
        }

        $apiKey = trim((string) $this->config['api_key']);
        if ($apiKey === '') {
            throw new AuthException('OpenAI не е конфигуриран. Добавете OPENAI_API_KEY в средата на backend услугата.', 503);
        }

        $content = [[
            'type' => 'input_text',
            'text' => $this->prompt(),
        ]];

        foreach ($images as $image) {
            $this->imageValidator->validateUpload($image);
            $tmp = (string) ($image['tmp_name'] ?? '');
            $bytes = file_get_contents($tmp);
            if ($bytes === false) {
                throw new AuthException('Изображението не можа да бъде прочетено.', 422);
            }

            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: 'image/jpeg';
            $content[] = [
                'type' => 'input_image',
                'image_url' => 'data:' . $mime . ';base64,' . base64_encode($bytes),
                'detail' => 'auto',
            ];
        }

        $payload = [
            'model' => (string) $this->config['product_model'],
            'store' => false,
            'input' => [[
                'role' => 'user',
                'content' => $content,
            ]],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'product_form_suggestion',
                    'strict' => true,
                    'schema' => $this->schema(),
                ],
            ],
            'max_output_tokens' => 1800,
        ];

        return $this->request($payload, $apiKey);
    }

    /** @param list<int> $imageIds @return array<string, mixed> */
    public function generateForProduct(int $productId, array $imageIds): array
    {
        $imageIds = array_values(array_unique(array_filter($imageIds, static fn (int $id): bool => $id > 0)));
        if ($imageIds === []) {
            throw new AuthException('Изберете поне едно изображение за AI анализ.', 422);
        }

        $records = ProductImage::query()
            ->where('product_id', $productId)
            ->whereIn('id', $imageIds)
            ->whereIn('role', [ProductImage::ROLE_FRONT, ProductImage::ROLE_GALLERY])
            ->get();

        if ($records->count() !== count($imageIds)) {
            throw new AuthException('Някое от избраните изображения не е прикачено към този продукт.', 422);
        }

        $storage = new ProductImageStorage();
        $files = [];
        foreach ($records as $record) {
            $path = $storage->absolutePath((string) $record->path);
            if (!is_file($path)) {
                throw new AuthException('Някое от избраните изображения липсва.', 422);
            }
            $files[] = [
                'name' => (string) $record->original_name,
                'type' => (string) $record->mime,
                'tmp_name' => $path,
                'error' => UPLOAD_ERR_OK,
                'size' => (int) (filesize($path) ?: $record->size),
            ];
        }

        return $this->generate($files);
    }

    private function prompt(): string
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (Category $category): array => [
                'id' => (int) $category->id,
                'name' => (string) $category->name,
            ])
            ->all();

        return implode("\n", [
            'Анализирай изображенията като един продукт за български онлайн магазин.',
            'Върни кратки, точни и редактируеми предложения на български език. Не измисляй марка, материал, размер или технически характеристики, които не се виждат.',
            'SKU да е кратко предложение с латински главни букви, цифри и тире. Цената е ориентировъчна в EUR.',
            'Краткото описание да е до 500 знака. Подробното описание може да използва само безопасни HTML тагове: p, ul, ol, li, strong, em.',
            'Избери category_id само от следния списък. Ако няма подходяща категория, върни null:',
            json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ]);
    }

    /** @return array<string, mixed> */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'name' => ['type' => ['string', 'null']],
                'sku' => ['type' => ['string', 'null']],
                'short_description' => ['type' => ['string', 'null']],
                'description' => ['type' => ['string', 'null']],
                'category_id' => ['type' => ['integer', 'null']],
                'price' => ['type' => ['number', 'null'], 'minimum' => 0],
                'seo_title' => ['type' => ['string', 'null']],
                'seo_description' => ['type' => ['string', 'null']],
            ],
            'required' => ['name', 'sku', 'short_description', 'description', 'category_id', 'price', 'seo_title', 'seo_description'],
        ];
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private function request(array $payload, string $apiKey): array
    {
        $url = (string) $this->config['api_url'] . '/responses';
        $curl = curl_init($url);
        if ($curl === false) {
            throw new AuthException('AI услугата не може да бъде стартирана.', 503);
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => (int) $this->config['timeout_seconds'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $raw = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if ($raw === false) {
            error_log('OpenAI product analysis failed [transport=curl].');
            throw new AuthException('Връзката с AI услугата е неуспешна. Опитайте отново.', 503);
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            throw new AuthException('AI услугата върна невалиден отговор.', 502);
        }

        if ($status < 200 || $status >= 300) {
            error_log('OpenAI product analysis rejected [status=' . $status . '].');
            $message = is_string($decoded['error']['message'] ?? null) ? $decoded['error']['message'] : 'заявката беше отхвърлена';
            throw new AuthException('AI анализът е неуспешен: ' . $message, 502);
        }

        $text = $decoded['output_text'] ?? null;
        if (!is_string($text) || $text === '') {
            foreach (($decoded['output'] ?? []) as $item) {
                foreach (($item['content'] ?? []) as $part) {
                    if (($part['type'] ?? null) === 'output_text' && is_string($part['text'] ?? null)) {
                        $text = $part['text'];
                        break 2;
                    }
                }
            }
        }

        $suggestion = is_string($text) ? json_decode($text, true) : null;
        if (!is_array($suggestion)) {
            throw new AuthException('AI анализът не върна полета в очаквания формат.', 502);
        }

        $categoryId = $suggestion['category_id'] ?? null;
        if (!is_int($categoryId) || !Category::query()->whereKey($categoryId)->where('is_active', true)->exists()) {
            $suggestion['category_id'] = null;
        }

        return $suggestion;
    }
}
