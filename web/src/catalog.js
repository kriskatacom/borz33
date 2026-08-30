import { notify } from './toast.js';
import PhotoSwipeLightbox from 'photoswipe/lightbox';
import 'photoswipe/style.css';

const STORAGE_KEY = 'borz33.catalog.columns';
const ALLOWED_COLUMNS = ['1', '2', '3', '4'];

export function mountCatalogGrid() {
    const filters = document.querySelector('.store-catalog-filters');
    const catalogLayout = document.querySelector('.store-catalog-layout');
    const openFilter = document.querySelector('[data-filter-open]');
    const closeFilters = [...document.querySelectorAll('[data-filter-close]')];
    const setFiltersOpen = (open) => {
        if (!filters) return;
        const mobile = window.matchMedia('(max-width: 52rem)').matches;
        filters.classList.toggle('is-open', mobile && open);
        catalogLayout?.classList.toggle('is-filters-collapsed', !mobile && !open);
        document.body.classList.toggle('store-filters-open', mobile && open);
        openFilter?.setAttribute('aria-expanded', String(open));
    };
    openFilter?.addEventListener('click', () => setFiltersOpen(true));
    closeFilters.forEach((button) => button.addEventListener('click', () => setFiltersOpen(false)));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') setFiltersOpen(false); });
    openFilter?.setAttribute('aria-expanded', String(!window.matchMedia('(max-width: 52rem)').matches));

    const grid = document.querySelector('.store-catalog-grid');
    const buttons = [...document.querySelectorAll('[data-catalog-columns]')];

    if (!grid || buttons.length === 0) return;

    const apply = (columns, persist = false) => {
        let value = ALLOWED_COLUMNS.includes(String(columns)) ? String(columns) : '4';
        if (window.matchMedia('(max-width: 34rem)').matches && Number(value) > 2) value = '2';
        else if (window.matchMedia('(max-width: 52rem)').matches && Number(value) > 3) value = '3';
        grid.dataset.columns = value;

        buttons.forEach((button) => {
            const active = button.dataset.catalogColumns === value;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', String(active));
        });

        if (persist) {
            try { localStorage.setItem(STORAGE_KEY, value); } catch (_) { /* Storage may be unavailable. */ }
        }
    };

    let saved = '4';
    try { saved = localStorage.getItem(STORAGE_KEY) || saved; } catch (_) { /* Use the default. */ }
    apply(saved);
    buttons.forEach((button) => button.addEventListener('click', () => apply(button.dataset.catalogColumns, true)));

    mountProductActions();
}

function escapeHtml(value) {
    const element = document.createElement('span');
    element.textContent = String(value ?? '');
    return element.innerHTML;
}

function csrfToken() {
    return document.querySelector('#store-cart-app')?.dataset.csrf ?? '';
}

function mountProductActions() {
    document.querySelectorAll('[data-quick-view]').forEach((button) => button.addEventListener('click', () => loadProduct(button.dataset.quickView, false, button)));
    document.querySelectorAll('[data-card-cart]').forEach((button) => button.addEventListener('click', async () => {
        if (button.dataset.inCart === 'true') {
            const response = await fetch('/cart/data', { headers: { Accept: 'application/json' } });
            const body = await response.json();
            window.dispatchEvent(new CustomEvent('store:cart-updated', { detail: { data: body.data, message: 'Продуктът е в количката.' } }));
            return;
        }
        loadProduct(button.dataset.cardCart, true, button);
    }));
    document.querySelectorAll('[data-card-qty-step]').forEach((button) => button.addEventListener('click', () => {
        const input = button.closest('.store-card-qty')?.querySelector('[data-card-qty]');
        if (!input) return;
        input.value = String(Math.max(1, Math.min(99, Number(input.value || 1) + Number(button.dataset.cardQtyStep))));
    }));
    window.addEventListener('store:cart-updated', (event) => updateCartButtons(event.detail?.data));
    window.addEventListener('store:cart-state', (event) => updateCartButtons(event.detail?.data));
}

function updateCartButtons(data) {
    const ids = new Set((data?.lines ?? []).map((line) => Number(line.product_id)));
    document.querySelectorAll('[data-card-cart]').forEach((button) => {
        const inCart = ids.has(Number(button.dataset.productId));
        button.dataset.inCart = String(inCart);
        button.classList.toggle('is-in-cart', inCart);
        const label = button.querySelector('[data-cart-label]');
        if (label) label.textContent = inCart ? 'В количката' : 'Добави';
    });
}

async function loadProduct(href, addDirectly, trigger) {
    if (!href || trigger.disabled) return;
    trigger.disabled = true;

    try {
        const response = await fetch(`${href.replace(/\/$/, '')}/quick-view`, { headers: { Accept: 'application/json' } });
        const body = await response.json();
        if (!response.ok) throw new Error(body?.message || 'Продуктът не може да бъде зареден.');

        const product = body.data;
        const available = product.config.variants.filter((variant) => Number(variant.stock) > 0);
        const selected = product.config.selected ?? {};
        const preferred = available.find((variant) => Object.entries(selected).every(([key, value]) => variant.values[key] === value)) ?? available[0];
        if (addDirectly && preferred && !product.config.fields.some((field) => field.required)) {
            await addToCart(product, preferred.id, cardQuantity(trigger));
        } else {
            openQuickView(product, cardQuantity(trigger));
        }
    } catch (error) {
        notify(error instanceof Error ? error.message : 'Възникна грешка.', 'error');
    } finally {
        trigger.disabled = false;
    }
}

function cardQuantity(trigger) {
    const value = Number(trigger.closest('[data-card-product]')?.querySelector('[data-card-qty]')?.value ?? 1);
    return Math.max(1, Math.min(99, value || 1));
}

async function addToCart(product, variantId, qty = 1) {
    const body = new URLSearchParams({ _token: csrfToken(), variant_id: String(variantId), qty: String(qty) });
    const response = await fetch(product.cartUrl, { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' }, body });
    const result = await response.json();
    if (!response.ok) throw new Error(result?.message || 'Продуктът не може да бъде добавен.');
    window.dispatchEvent(new CustomEvent('store:cart-updated', { detail: { data: result.data, message: result.message } }));
    notify(result.message);
}

function openQuickView(product, qty = 1) {
    document.querySelector('.store-quick-view')?.remove();
    const config = product.config;
    const requiresPersonalization = config.fields.some((field) => field.required);
    const selection = { ...config.selected };
    const options = config.options.map((option) => `<fieldset data-quick-option-group="${escapeHtml(option.slug)}"><legend>${escapeHtml(option.name)}</legend><div>${option.values.map((value) => `<button type="button" data-quick-option="${escapeHtml(option.slug)}" data-quick-value="${escapeHtml(value.slug)}" class="${selection[option.slug] === value.slug ? 'is-selected' : ''}">${value.hex ? `<i style="--option-color:${escapeHtml(value.hex)}" aria-hidden="true"></i>` : ''}<span>${escapeHtml(value.name)}</span></button>`).join('')}</div></fieldset>`).join('');
    const modal = document.createElement('div');
    modal.className = 'store-quick-view';
    modal.innerHTML = `<button class="store-quick-view-backdrop" type="button" data-quick-close aria-label="Затвори"></button><section role="dialog" aria-modal="true" aria-label="Бърз преглед"><button class="store-quick-view-close" type="button" data-quick-close aria-label="Затвори"><svg viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"/></svg></button><div class="store-quick-view-media">${product.image ? `<button type="button" data-quick-lightbox aria-label="Отвори галерията"><img src="${escapeHtml(product.image)}" alt="${escapeHtml(product.imageAlt)}"><span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4M11 8v6M8 11h6"/></svg></span></button>` : ''}</div><div class="store-quick-view-copy"><p>Бърз преглед</p><h2>${escapeHtml(product.name)}</h2><strong data-quick-price>${escapeHtml(product.price)}</strong><div class="store-quick-view-options">${options}</div><p class="store-quick-view-error" data-quick-error></p><div class="store-quick-view-actions"><button type="button" data-quick-cart>Добави в количката</button><a href="${escapeHtml(product.href)}">Към продукта</a></div></div></section>`;
    document.body.append(modal);
    document.body.classList.add('store-quick-open');

    const gallery = Array.isArray(config.gallery) ? config.gallery : [];
    const lightbox = gallery.length > 0 ? new PhotoSwipeLightbox({
        dataSource: gallery.map((image) => ({ src: image.url, width: 1200, height: 1500, alt: image.alt })),
        pswpModule: () => import('photoswipe'),
        bgOpacity: 0.92,
        showHideAnimationType: 'zoom',
    }) : null;
    lightbox?.init();
    modal.querySelector('[data-quick-lightbox]')?.addEventListener('click', () => lightbox?.loadAndOpen(0));
    const close = () => { lightbox?.destroy(); modal.remove(); document.body.classList.remove('store-quick-open'); };
    modal.querySelectorAll('[data-quick-close]').forEach((button) => button.addEventListener('click', close));
    const variant = () => config.variants.find((item) => Object.entries(selection).every(([key, value]) => item.values[key] === value));
    const refresh = () => {
        const current = variant();
        modal.querySelector('[data-quick-price]').textContent = current ? new Intl.NumberFormat('bg-BG', { style: 'currency', currency: 'EUR' }).format(Number(current.price)) : product.price;
        modal.querySelector('[data-quick-cart]').disabled = !current || Number(current.stock) < 1 || requiresPersonalization;
        modal.querySelector('[data-quick-error]').textContent = requiresPersonalization ? 'Задължителната персонализация се попълва в продуктовата страница.' : (!current || Number(current.stock) < 1 ? 'Този вариант не е наличен.' : '');
    };
    modal.querySelectorAll('[data-quick-option]').forEach((button) => button.addEventListener('click', () => {
        selection[button.dataset.quickOption] = button.dataset.quickValue;
        modal.querySelectorAll(`[data-quick-option="${CSS.escape(button.dataset.quickOption)}"]`).forEach((item) => item.classList.toggle('is-selected', item === button));
        refresh();
    }));
    modal.querySelector('[data-quick-cart]').addEventListener('click', async (event) => {
        const current = variant();
        if (!current) return;
        event.currentTarget.disabled = true;
        try { await addToCart(product, current.id, qty); close(); } catch (error) { modal.querySelector('[data-quick-error]').textContent = error.message; event.currentTarget.disabled = false; }
    });
    refresh();
}
