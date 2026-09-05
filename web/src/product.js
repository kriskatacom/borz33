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
      zoomActive: false,
      zoomX: 50,
      zoomY: 50,

      init() {
        this.syncVariantImage();
      },

      zoomStyle() {
        if (!this.image) return {};

        const zoomFactor = 4.5;
        // Keep the point under the cursor in the centre of the lens,
        // including when the cursor is close to an image edge.
        const backgroundPosition = (coordinate) =>
          ((zoomFactor * (coordinate / 100) - 0.5) / (zoomFactor - 1)) * 100;

        return {
          left: `${this.zoomX}%`,
          top: `${this.zoomY}%`,
          backgroundImage: `url("${this.image.url}")`,
          backgroundSize: `${zoomFactor * 100}% ${zoomFactor * 100}%`,
          backgroundPosition: `${backgroundPosition(this.zoomX)}% ${backgroundPosition(this.zoomY)}%`,
        };
      },

      startZoom(event) {
        if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
        this.zoomActive = true;
        this.moveZoom(event);
      },

      moveZoom(event) {
        const rect = event.currentTarget.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) return;
        this.zoomX = Math.max(0, Math.min(100, ((event.clientX - rect.left) / rect.width) * 100));
        this.zoomY = Math.max(0, Math.min(100, ((event.clientY - rect.top) / rect.height) * 100));
      },

      stopZoom() {
        this.zoomActive = false;
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
          this.stopZoom();
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

function reviewStars(rating) {
  const value = Math.max(1, Math.min(5, Number(rating) || 0));

  return `${'★'.repeat(value)}${'☆'.repeat(5 - value)}`;
}

function reviewDate(value) {
  if (typeof value !== 'string' || value === '') {
    return '';
  }

  return new Intl.DateTimeFormat('bg-BG', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(value));
}

function setReviewRating(form, rating) {
  const value = Math.max(0, Math.min(5, Number(rating) || 0));
  const input = form.querySelector('[data-review-rating-input]');
  const label = form.querySelector('[data-review-rating-label]');

  if (input) {
    input.value = String(value);
  }

  form.querySelectorAll('[data-review-rating-value]').forEach((button) => {
    const buttonRating = Number(button.dataset.reviewRatingValue);
    const selected = buttonRating <= value;
    button.classList.toggle('is-filled', selected);
    button.setAttribute('aria-pressed', String(buttonRating === value));
  });

  if (label) {
    label.hidden = value === 0;
    label.textContent = value === 1 ? '1 звезда' : `${value} звезди`;
  }
}

function reviewItem(review, ownReview, editUrl) {
  const article = document.createElement('article');
  article.className = `store-review${ownReview ? ' is-owned' : ''}`;
  article.dataset.reviewItem = String(review.id);

  const header = document.createElement('header');
  const author = document.createElement('span');
  const authorName = document.createElement('strong');
  authorName.textContent = review.author;
  const stars = document.createElement('span');
  stars.className = 'store-review-stars';
  stars.setAttribute('aria-label', `Оценка ${review.rating} от 5`);
  const starContent = document.createElement('i');
  starContent.setAttribute('aria-hidden', 'true');
  starContent.textContent = reviewStars(review.rating);
  stars.append(starContent);
  author.append(authorName, stars);

  const time = document.createElement('time');
  time.dateTime = review.created_at_iso || '';
  time.textContent = review.created_at || reviewDate(review.created_at_iso);
  header.append(author, time);

  const body = document.createElement('p');
  body.textContent = review.body;
  article.append(header, body);

  if (ownReview) {
    const actions = document.createElement('div');
    actions.className = 'store-review-actions';
    const edit = document.createElement('button');
    edit.type = 'button';
    edit.className = 'store-review-edit';
    edit.dataset.reviewEdit = '';
    edit.dataset.reviewId = String(review.id);
    edit.dataset.reviewUrl = editUrl;
    edit.dataset.reviewRating = String(review.rating);
    edit.dataset.reviewBody = review.body;
    edit.textContent = 'Редактирай отзива';
    actions.append(edit);
    article.append(actions);
  }

  return article;
}

function updateReviewSummary(root, summary) {
  const count = Number(summary?.count) || 0;
  const average = summary?.average === null || summary?.average === undefined ? null : Number(summary.average);

  root.querySelectorAll('[data-review-count]').forEach((element) => {
    element.textContent = String(count);
  });

  const averageElement = root.querySelector('[data-review-average]');
  if (!averageElement) {
    return;
  }

  const showAverage = Number.isFinite(average) && average !== null && count > 0;
  averageElement.hidden = !showAverage;

  if (!showAverage) {
    return;
  }

  const displayed = average.toLocaleString('bg-BG', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
  averageElement.setAttribute('aria-label', `Средна оценка ${displayed} от 5`);
  const stars = averageElement.querySelector('i');
  const value = averageElement.querySelector('strong');

  if (stars) {
    stars.textContent = reviewStars(Math.round(average));
  }

  if (value) {
    value.textContent = displayed;
  }
}

export function mountProductReviews() {
  const root = document.querySelector('[data-product-reviews]');
  const composer = root?.querySelector('[data-review-composer]');
  const form = root?.querySelector('[data-review-form]');

  if (!root || !composer || !form) {
    return;
  }

  const error = form.querySelector('[data-review-error]');
  const title = form.querySelector('[data-review-form-title]');
  const help = form.querySelector('[data-review-form-help]');
  const body = form.querySelector('[data-review-body]');
  const submit = form.querySelector('[data-review-submit]');
  const createUrl = form.dataset.reviewCreateUrl || root.dataset.reviewCreateUrl || form.action;
  const showError = (message = '') => {
    if (!error) {
      return;
    }

    error.hidden = message === '';
    error.textContent = message;
  };
  const closeComposer = () => {
    if (composer instanceof HTMLDialogElement && composer.open) {
      composer.close();
    }
    showError();
  };
  const openComposer = (mode, review = null) => {
    const editing = mode === 'edit' && review !== null;
    form.dataset.reviewMode = editing ? 'edit' : 'create';
    form.action = editing ? review.url : createUrl;

    if (title) {
      title.textContent = editing ? 'Редактирайте отзива си' : 'Оставете отзив';
    }
    if (help) {
      help.textContent = editing ? 'Променете текста или оценката и запазете промените.' : 'Споделете впечатленията си от продукта.';
    }
    if (submit) {
      submit.textContent = editing ? 'Запази промените' : 'Публикувай отзив';
    }
    if (body) {
      body.value = editing ? review.body : '';
    }

    setReviewRating(form, editing ? review.rating : 0);
    showError();
    if (composer instanceof HTMLDialogElement && !composer.open) {
      composer.showModal();
    }
    window.requestAnimationFrame(() => body?.focus());
  };

  root.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target : null;
    const rating = target?.closest('[data-review-rating-value]');

    if (rating instanceof HTMLButtonElement) {
      setReviewRating(form, Number(rating.dataset.reviewRatingValue));
      return;
    }

    if (target?.closest('[data-review-create]')) {
      openComposer('create');
      return;
    }

    const edit = target?.closest('[data-review-edit]');
    if (edit instanceof HTMLButtonElement) {
      openComposer('edit', {
        url: edit.dataset.reviewUrl || '',
        rating: Number(edit.dataset.reviewRating),
        body: edit.dataset.reviewBody || '',
      });
      return;
    }

    if (target?.closest('[data-review-cancel]')) {
      closeComposer();
    }
  });

  composer.addEventListener('cancel', () => {
    showError();
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const mode = form.dataset.reviewMode || 'create';
    const formData = new FormData(form);
    const rating = Number(formData.get('rating'));

    if (rating < 1 || rating > 5) {
      showError('Изберете оценка от 1 до 5 звезди.');
      return;
    }

    if (!form.reportValidity()) {
      return;
    }

    showError();
    if (submit instanceof HTMLButtonElement) {
      submit.disabled = true;
    }

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
        credentials: 'same-origin',
      });
      const payload = await response.json().catch(() => null);

      if (!response.ok || payload?.success !== true || !payload?.data?.review) {
        throw new Error(payload?.message || 'Отзивът не може да бъде запазен. Опитайте отново.');
      }

      const review = payload.data.review;
      const editUrl = `${createUrl.replace(/\/$/, '')}/${review.id}`;
      const item = reviewItem(review, true, editUrl);
      const current = root.querySelector(`[data-review-item="${review.id}"]`);
      const list = root.querySelector('[data-reviews-list]');

      if (current) {
        current.replaceWith(item);
      } else {
        list?.prepend(item);
      }

      root.querySelector('[data-reviews-empty]')?.setAttribute('hidden', '');
      updateReviewSummary(root, payload.data.summary);
      closeComposer();
      notify(payload.message || (mode === 'edit' ? 'Отзивът Ви е обновен.' : 'Отзивът е публикуван.'), 'success');
    } catch (reason) {
      showError(reason instanceof Error ? reason.message : 'Възникна грешка. Опитайте отново.');
    } finally {
      if (submit instanceof HTMLButtonElement) {
        submit.disabled = false;
      }
    }
  });
}
