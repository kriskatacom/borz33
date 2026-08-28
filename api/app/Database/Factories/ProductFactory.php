<?php

declare(strict_types=1);

namespace App\Database\Factories;

use App\Models\Product;
use App\Services\Products\ProductAdminService;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Support\Str;

final class ProductFactory
{
    private static int $next = 0;

    /** @var array<string, mixed> */
    private array $overrides = [];

    /** @var list<array{name: string, slug: string, hex: string}> */
    private const COLORS = [
        ['name' => 'Черно', 'slug' => 'black', 'hex' => '#111111'],
        ['name' => 'Бяло', 'slug' => 'white', 'hex' => '#F5F5F5'],
        ['name' => 'Сиво', 'slug' => 'gray', 'hex' => '#6B7280'],
        ['name' => 'Тъмно синьо', 'slug' => 'navy', 'hex' => '#1E3A5F'],
        ['name' => 'Бордо', 'slug' => 'burgundy', 'hex' => '#7F1D1D'],
        ['name' => 'Маслинено', 'slug' => 'olive', 'hex' => '#4D5B2F'],
        ['name' => 'Бежово', 'slug' => 'beige', 'hex' => '#D4C4A8'],
    ];

    /** @var list<array{name: string, slug: string}> */
    private const SIZES = [
        ['name' => 'S', 'slug' => 's'],
        ['name' => 'M', 'slug' => 'm'],
        ['name' => 'L', 'slug' => 'l'],
        ['name' => 'XL', 'slug' => 'xl'],
        ['name' => 'XXL', 'slug' => 'xxl'],
    ];

    /** @var list<string> */
    private const STYLES = [
        'Класическа тениска',
        'Овърсайз тениска',
        'Тениска с V-деколте',
        'Тениска с яка',
        'Поло тениска',
        'Детска тениска',
        'Спортна тениска',
        'Ленена тениска',
        'Тениска с дълъг ръкав',
        'Базова тениска',
    ];

    private function __construct(
        private readonly Generator $faker
    ) {
    }

    public static function new(): self
    {
        return new self(FakerFactory::create('bg_BG'));
    }

    public function inactive(): self
    {
        return $this->state(['is_active' => false]);
    }

    public function personalized(): self
    {
        return $this->state(['personalization_enabled' => true]);
    }

    public function plain(): self
    {
        return $this->state(['personalization_enabled' => false]);
    }

    /** @param array<string, mixed> $attributes */
    public function state(array $attributes): self
    {
        $clone = clone $this;
        $clone->overrides = array_merge($clone->overrides, $attributes);

        return $clone;
    }

    /** @return array<string, mixed> */
    public function make(): array
    {
        self::$next++;
        $sequence = self::$next;
        $style = self::STYLES[($sequence - 1) % count(self::STYLES)];
        $name = $style . ' №' . $sequence;
        $sku = sprintf('SEED-%02d-%s', $sequence, strtoupper(bin2hex(random_bytes(2))));
        $price = $this->faker->randomElement([19.90, 24.90, 29.90, 34.90, 39.90, 44.90]);
        $onSale = $this->faker->boolean(35);
        $personalized = (bool) ($this->overrides['personalization_enabled'] ?? $this->faker->boolean(40));
        $colors = $this->faker->randomElements(self::COLORS, random_int(3, 5));
        $sizes = self::SIZES;
        $slug = 'seed-' . $sequence . '-' . (Str::slug($style, '-', 'bg') ?: 'teniska');

        $payload = [
            'name' => $name,
            'slug' => $slug,
            'sku' => $sku,
            'short_description' => 'Памучна тениска за ежедневно носене. Мека, лека и удобна кройка.',
            'description' => $this->faker->paragraphs(2, true),
            'price' => $price,
            'compare_at_price' => $onSale ? round($price + 10, 2) : null,
            'is_active' => true,
            'personalization_enabled' => $personalized,
            'personalization_label' => $personalized ? 'Текст за печат' : null,
            'personalization_description' => $personalized
                ? 'Име или кратък надпис, който отпечатваме на гърба. Без емоджи.'
                : null,
            'personalization_required' => false,
            'personalization_max_length' => 16,
            'sort_order' => $sequence,
            'parameters' => [
                ['name' => 'Материя', 'value' => $this->faker->randomElement(['100% памук', '95% памук / 5% еластан', 'Органичен памук']), 'sort_order' => 0],
                ['name' => 'Грамаж', 'value' => $this->faker->randomElement(['160 g/m²', '180 g/m²', '200 g/m²']), 'sort_order' => 1],
                ['name' => 'Кройка', 'value' => $this->faker->randomElement(['Regular', 'Oversized', 'Slim']), 'sort_order' => 2],
            ],
            'options' => [
                [
                    'name' => 'Размер',
                    'slug' => 'size',
                    'sort_order' => 0,
                    'values' => array_map(
                        static fn (array $size, int $index): array => [
                            'name' => $size['name'],
                            'slug' => $size['slug'],
                            'sort_order' => $index,
                        ],
                        $sizes,
                        array_keys($sizes)
                    ),
                ],
                [
                    'name' => 'Цвят',
                    'slug' => 'color',
                    'sort_order' => 1,
                    'values' => array_values(array_map(
                        static fn (array $color, int $index): array => [
                            'name' => $color['name'],
                            'slug' => $color['slug'],
                            'hex_color' => $color['hex'],
                            'sort_order' => $index,
                        ],
                        $colors,
                        array_keys($colors)
                    )),
                ],
            ],
            'variants' => $this->makeVariants($sku, $price, $sizes, $colors),
            'personalization_fields' => $personalized
                ? [[
                    'name' => 'Надпис',
                    'description' => 'До 16 символа.',
                    'field_type' => 'text',
                    'is_required' => false,
                    'max_length' => 16,
                    'sort_order' => 0,
                ]]
                : [],
        ];

        return array_merge($payload, $this->overrides);
    }

    public function create(): Product
    {
        $data = $this->make();
        $trashed = !empty($data['deleted_at']);
        unset($data['deleted_at']);

        $product = (new ProductAdminService())->create($data);

        if ($trashed) {
            $product->delete();
        }

        return $product->fresh() ?? $product;
    }

    /**
     * @param list<array{name: string, slug: string}> $sizes
     * @param list<array{name: string, slug: string, hex: string}> $colors
     * @return list<array<string, mixed>>
     */
    private function makeVariants(string $sku, float $price, array $sizes, array $colors): array
    {
        $pairs = [];

        foreach ($colors as $color) {
            foreach ($sizes as $size) {
                $pairs[] = [$size, $color];
            }
        }

        shuffle($pairs);
        $pairs = array_slice($pairs, 0, random_int(6, min(10, count($pairs))));
        $variants = [];

        foreach ($pairs as $index => [$size, $color]) {
            $variants[] = [
                'sku' => sprintf('%s-%s-%s', $sku, strtoupper($color['slug']), strtoupper($size['slug'])),
                'name' => $color['name'] . ' / ' . $size['name'],
                'price' => $price,
                'stock' => random_int(0, 28),
                'is_default' => $index === 0,
                'is_active' => $this->faker->boolean(92),
                'sort_order' => $index,
                'option_values' => [
                    ['option' => 'size', 'value' => $size['slug']],
                    ['option' => 'color', 'value' => $color['slug']],
                ],
            ];
        }

        return $variants;
    }
}
