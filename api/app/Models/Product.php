<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'short_description',
        'description',
        'price',
        'compare_at_price',
        'is_active',
        'personalization_enabled',
        'personalization_label',
        'personalization_description',
        'personalization_required',
        'personalization_max_length',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'is_active' => 'boolean',
            'personalization_enabled' => 'boolean',
            'personalization_required' => 'boolean',
            'personalization_max_length' => 'integer',
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
        return $this->personalization_enabled === true;
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

    /** @return Collection<int, ProductPersonalizationField> */
    public function personalizationInputs(): Collection
    {
        $fields = $this->personalizationFields;

        if ($fields->isNotEmpty() || !$this->allowsPersonalization()) {
            return $fields;
        }

        $field = new ProductPersonalizationField();
        $field->forceFill([
            'product_id' => $this->id,
            'name' => $this->personalization_label ?: 'Персонализация',
            'description' => $this->personalization_description,
            'field_type' => ProductPersonalizationField::TYPE_TEXTAREA,
            'is_required' => $this->personalization_required,
            'max_length' => $this->personalization_max_length,
            'sort_order' => 0,
        ]);

        return new Collection([$field]);
    }
}
