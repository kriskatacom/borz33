import PhotoSwipeLightbox from 'photoswipe/lightbox';
import 'photoswipe/style.css';
import { notify } from './toast.js';

function money(value) {
  const amount = typeof value === 'number' ? value : Number(value);

  if (!Number.isFinite(amount)) {
    return '';
  }

  return new Intl.NumberFormat('bg-BG', {
    style: 'currency',
    currency: 'EUR',
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
      submitting: false,
      photoSwipe: null,
      lightboxOpening: false,
      lightboxImageData: {},

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

      async lightboxItem(image) {
        const cached = this.lightboxImageData[image.url];

        if (cached) {
          return { ...image, ...cached };
        }

        const knownWidth = Number(image.width);
        const knownHeight = Number(image.height);

        if (knownWidth > 0 && knownHeight > 0) {
          const dimensions = { width: knownWidth, height: knownHeight };
          this.lightboxImageData[image.url] = dimensions;

          return { ...image, ...dimensions };
        }

        const dimensions = await new Promise((resolve) => {
          const asset = new Image();

          asset.onload = () => resolve({
            width: asset.naturalWidth || 1600,
            height: asset.naturalHeight || 1600,
          });
          asset.onerror = () => resolve({ width: 1600, height: 1600 });
          asset.src = image.url;
        });

        this.lightboxImageData[image.url] = dimensions;

        return { ...image, ...dimensions };
      },

      async openLightbox() {
        if (!this.image || this.lightboxOpening || this.photoSwipe) {
          return;
        }

        this.lightboxOpening = true;

        try {
          const images = this.images.slice();
          const startIndex = Math.min(this.imageIndex, images.length - 1);
          const dataSource = await Promise.all(images.map((image) => this.lightboxItem(image)));
          const lightbox = new PhotoSwipeLightbox({
            dataSource: dataSource.map((image) => ({
              src: image.url,
              width: image.width,
              height: image.height,
              alt: image.alt || this.name,
            })),
            pswpModule: () => import('photoswipe'),
            bgOpacity: 0.92,
            initialZoomLevel: 'fit',
            paddingFn: (viewportSize) => {
              const compactViewport = viewportSize.x < 640;
              const verticalPadding = compactViewport ? 12 : 24;

              return {
                top: verticalPadding,
                bottom: verticalPadding,
                left: compactViewport ? 12 : 32,
                right: compactViewport ? 12 : 32,
              };
            },
            showHideAnimationType: 'zoom',
            mainClass: 'store-pdp-lightbox',
          });

          lightbox.on('change', () => {
            this.imageIndex = lightbox.pswp?.currIndex ?? this.imageIndex;
          });
          lightbox.on('destroy', () => {
            this.photoSwipe = null;
          });
          lightbox.init();
          this.photoSwipe = lightbox;
          lightbox.loadAndOpen(startIndex);
        } finally {
          this.lightboxOpening = false;
        }
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

      async onSubmit(event) {
        const message = this.fieldError();

        if (!this.canBuy || message !== '') {
          event.preventDefault();
          this.error = message || (this.variant === null ? 'Изберете наличен вариант.' : 'Продуктът е изчерпан.');

          return;
        }

        event.preventDefault();
        this.error = '';
        this.submitting = true;

        try {
          const response = await fetch(event.currentTarget.action, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: new FormData(event.currentTarget),
          });
          const body = await response.json();

          if (!response.ok) {
            throw new Error(body?.message || 'Продуктът не може да бъде добавен.');
          }

          window.dispatchEvent(new CustomEvent('store:cart-updated', {
            detail: { data: body.data, message: body.message },
          }));
        } catch (reason) {
          this.error = reason instanceof Error ? reason.message : 'Възникна грешка. Опитайте отново.';
          notify(this.error, 'error');
        } finally {
          this.submitting = false;
        }
      },
    }));
  });
}
