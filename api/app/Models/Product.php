<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'category_id',
        'sku',
        'short_description',
        'description',
        'price',
        'compare_at_price',
        'weight_grams',
        'is_active',
        'personalization_enabled',
        'personalization_label',
        'personalization_description',
        'personalization_required',
        'personalization_max_length',
        'personalization_override',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'weight_grams' => 'integer',
            'is_active' => 'boolean',
            'personalization_enabled' => 'boolean',
            'personalization_required' => 'boolean',
            'personalization_max_length' => 'integer',
            'personalization_override' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected function slug(): Attribute
    {
        return Attribute::make(
            set: static fn (string $value): string => strtolower(trim($value)),
        );
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function allowsPersonalization(): bool
    {
        return (bool) ($this->effectivePersonalization()['enabled'] ?? false);
    }

    /** @return array<string, mixed> */
    public function effectivePersonalization(): array
    {
        if ($this->personalization_override === true) {
            return [
                'enabled' => (bool) $this->personalization_enabled,
                'label' => $this->personalization_label,
                'description' => $this->personalization_description,
                'required' => (bool) $this->personalization_required,
                'max_length' => (int) $this->personalization_max_length,
                'fields' => $this->personalizationFields->map(static fn (ProductPersonalizationField $field): array => [
                    'name' => (string) $field->name,
                    'description' => $field->description,
                    'field_type' => (string) $field->field_type,
                    'is_required' => (bool) $field->is_required,
                    'max_length' => (int) $field->max_length,
                    'sort_order' => (int) $field->sort_order,
                ])->values()->all(),
            ];
        }

        return SiteSetting::query()->first()?->product_personalization_default ?? [
            'enabled' => false,
            'label' => null,
            'description' => null,
            'required' => false,
            'max_length' => 80,
            'fields' => [],
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_favorite_products');
    }

    public function viewedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_recently_viewed_products');
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(ProductParameter::class)->orderBy('sort_order')->orderBy('id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('sort_order')->orderBy('id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function personalizationFields(): HasMany
    {
        return $this->hasMany(ProductPersonalizationField::class)->orderBy('sort_order')->orderBy('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->latest('id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('role')->orderBy('sort_order')->orderBy('id');
    }

    public function frontImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('role', ProductImage::ROLE_FRONT);
    }

    public function galleryImages(): HasMany
    {
        return $this->hasMany(ProductImage::class)
            ->where('role', ProductImage::ROLE_GALLERY)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /** @return Collection<int, ProductPersonalizationField> */
    public function personalizationInputs(): Collection
    {
        $config = $this->effectivePersonalization();
        $fields = $this->personalization_override === true
            ? $this->personalizationFields
            : new Collection(array_map(static function (array $row): ProductPersonalizationField {
                $field = new ProductPersonalizationField();
                $field->forceFill($row);
                return $field;
            }, is_array($config['fields'] ?? null) ? $config['fields'] : []));

        if ($fields->isNotEmpty() || !$this->allowsPersonalization()) {
            return $fields;
        }

        $field = new ProductPersonalizationField();
        $field->forceFill([
            'product_id' => $this->id,
            'name' => ($config['label'] ?? null) ?: 'Персонализация',
            'description' => $config['description'] ?? null,
            'field_type' => ProductPersonalizationField::TYPE_TEXTAREA,
            'is_required' => (bool) ($config['required'] ?? false),
            'max_length' => (int) ($config['max_length'] ?? 80),
            'sort_order' => 0,
        ]);

        return new Collection([$field]);
    }
}
