function money(value) {
  const amount = typeof value === 'number' ? value : Number(value);

  if (!Number.isFinite(amount)) {
    return '';
  }

  return new Intl.NumberFormat('bg-BG', {
    style: 'currency',
    currency: 'BGN',
  }).format(amount);
}

function sameValues(left, right) {
  const keys = Object.keys(left ?? {});

  if (keys.length !== Object.keys(right ?? {}).length) {
    return false;
  }

  return keys.every((key) => left[key] === right[key]);
}

export function registerStoreProduct(Alpine) {
  document.addEventListener('alpine:init', () => {
    Alpine.data('storeProduct', (config = {}) => ({
      name: typeof config.name === 'string' ? config.name : '',
      gallery: Array.isArray(config.gallery) ? config.gallery : [],
      options: Array.isArray(config.options) ? config.options : [],
      variants: Array.isArray(config.variants) ? config.variants : [],
      selected: { ...(config.selected ?? {}) },
      qty: Number.isFinite(Number(config.qty)) ? Math.max(1, Number(config.qty)) : 1,
      fields: Array.isArray(config.fields) ? config.fields.map((field) => ({ ...field, value: field.value ?? '' })) : [],
      imageIndex: 0,
      error: '',
      lightbox: false,

      init() {
        this.syncVariantImage();
      },

      get variant() {
        return this.variants.find((item) => sameValues(item.values, this.selected)) ?? null;
      },

      get images() {
        const current = this.variant?.image;
        const base = this.gallery;

        if (current && !base.some((image) => image.url === current.url)) {
          return [current, ...base];
        }

        return base;
      },

      get image() {
        return this.images[this.imageIndex] ?? this.images[0] ?? null;
      },

      get price() {
        return this.variant?.price ?? null;
      },

      get compare() {
        return this.variant?.compare_at_price ?? null;
      },

      get onSale() {
        return this.compare !== null && Number(this.compare) > Number(this.price);
      },

      get sku() {
        return this.variant?.sku ?? '';
      },

      get stock() {
        return this.variant ? Number(this.variant.stock) : 0;
      },

      get inStock() {
        return this.variant !== null && this.stock > 0;
      },

      get maxQty() {
        if (!this.variant) {
          return 1;
        }

        return Math.max(1, Math.min(99, this.stock));
      },

      get canBuy() {
        return this.inStock && this.qty >= 1 && this.qty <= this.maxQty && this.fieldError() === '';
      },

      format: money,

      fieldError() {
        for (const field of this.fields) {
          const text = String(field.value ?? '').trim();

          if (field.required && text === '') {
            return `Полето ${field.name} е задължително.`;
          }

          if (field.max && text.length > field.max) {
            return `Полето ${field.name} трябва да бъде най-много ${field.max} символа.`;
          }
        }

        return '';
      },

      status() {
        if (this.variant === null) {
          return 'Тази комбинация не е налична.';
        }

        if (!this.inStock) {
          return 'Изчерпан';
        }

        if (this.stock <= 5) {
          return `Остават ${this.stock} бр.`;
        }

        return 'В наличност';
      },

      available(optionSlug, valueSlug) {
        const next = { ...this.selected, [optionSlug]: valueSlug };

        return this.variants.some((item) =>
          Object.entries(next).every(([key, value]) => item.values[key] === value)
        );
      },

      pick(optionSlug, valueSlug) {
        this.error = '';
        this.selected = { ...this.selected, [optionSlug]: valueSlug };

        if (this.variant !== null) {
          this.syncVariantImage();
          this.qty = Math.min(this.qty, this.maxQty);

          return;
        }

        const match = this.variants.find((item) => item.values[optionSlug] === valueSlug && item.stock > 0)
          ?? this.variants.find((item) => item.values[optionSlug] === valueSlug);

        if (match) {
          this.selected = { ...match.values };
          this.syncVariantImage();
          this.qty = Math.min(this.qty, this.maxQty);
        }
      },

      setImage(index) {
        if (index >= 0 && index < this.images.length) {
          this.imageIndex = index;
        }
      },

      stepImage(step) {
        if (this.images.length === 0) {
          return;
        }

        this.imageIndex = (this.imageIndex + step + this.images.length) % this.images.length;
      },

      openLightbox() {
        if (!this.image) {
          return;
        }

        this.lightbox = true;
        document.documentElement.classList.add('is-pdp-lightbox');
        this.$nextTick(() => this.$refs.lightboxClose?.focus());
      },

      closeLightbox() {
        if (!this.lightbox) {
          return;
        }

        this.lightbox = false;
        document.documentElement.classList.remove('is-pdp-lightbox');
      },

      minus() {
        this.qty = Math.max(1, this.qty - 1);
      },

      plus() {
        this.qty = Math.min(this.maxQty, this.qty + 1);
      },

      syncVariantImage() {
        const current = this.variant?.image;

        if (!current) {
          this.imageIndex = 0;

          return;
        }

        const index = this.images.findIndex((image) => image.url === current.url);
        this.imageIndex = index >= 0 ? index : 0;
      },

      onSubmit(event) {
        const message = this.fieldError();

        if (!this.canBuy || message !== '') {
          event.preventDefault();
          this.error = message || (this.variant === null ? 'Изберете наличен вариант.' : 'Продуктът е изчерпан.');

          return;
        }

        this.error = '';
      },
    }));
  });
}
