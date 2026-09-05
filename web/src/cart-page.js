import { notify } from './toast.js';

function escapeHtml(value) {
  const node = document.createElement('span');
  node.textContent = String(value ?? '');
  return node.innerHTML;
}

function money(value) {
  return new Intl.NumberFormat('bg-BG', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(Number(value ?? 0));
}

function emptyState() {
  return `<div class="store-empty-state store-empty-state--cart" data-cart-empty>
    <img src="/images/empty-cart.webp" alt="" width="768" height="512">
    <div class="store-empty-state-copy">
      <p class="store-empty-state-eyebrow">Тук все още е празно</p>
      <h1 class="store-empty-state-title">Количка</h1>
      <p>Разгледайте продуктите и добавете нещо, което ви харесва. Избраните артикули ще ви очакват тук.</p>
      <a class="store-empty-state-action" href="/catalog">Разгледайте каталога</a>
    </div>
  </div>`;
}

function lineHtml(line, csrf) {
  const image = line.image ? `<img src="${escapeHtml(line.image)}" alt="${escapeHtml(line.alt)}" width="96" height="120">` : '';
  const options = line.options ? `<p class="store-cart-meta">${escapeHtml(line.options)}</p>` : '';
  const sku = line.sku ? `<p class="store-cart-meta">Код ${escapeHtml(line.sku)}</p>` : '';
  const notes = (Array.isArray(line.notes) ? line.notes : []).map((note) => `<p class="store-cart-meta">${escapeHtml(note)}</p>`).join('');
  const qty = Number(line.qty ?? 1);
  const index = Number(line.index);

  return `<li class="store-cart-item" data-cart-line="${index}">
    <a class="store-cart-thumb" href="${escapeHtml(line.href)}">${image}</a>
    <div class="store-cart-info">
      <a class="store-cart-name" href="${escapeHtml(line.href)}">${escapeHtml(line.name)}</a>
      ${options}${sku}
      <p class="store-cart-weight">Тегло: <strong>${escapeHtml(line.weight)}</strong> · за това количество: <strong>${escapeHtml(line.total_weight)}</strong></p>
      ${notes}
    </div>
    <div class="store-cart-actions">
      <div class="store-cart-control-row">
        <form method="post" action="/cart/${index}" class="store-pdp-qty" data-cart-action>
          <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
          <button type="submit" name="qty" value="${Math.max(0, qty - 1)}" aria-label="Намали">−</button>
          <span>${qty}</span>
          <button type="submit" name="qty" value="${Math.min(99, qty + 1)}" aria-label="Увеличи">+</button>
        </form>
        <form method="post" action="/cart/${index}/delete" data-cart-action>
          <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
          <button type="submit" class="store-cart-remove" aria-label="Премахни продукта" title="Премахни"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg></button>
        </form>
      </div>
      <p class="store-cart-price">${money(line.total)}</p>
    </div>
  </li>`;
}

function cartContent(data, csrf) {
  const lines = Array.isArray(data?.lines) ? data.lines : [];
  if (lines.length === 0) return emptyState();

  return `<ul class="store-cart-list">${lines.map((line) => lineHtml(line, csrf)).join('')}</ul>
    <div class="store-cart-checkout">
      <div class="store-cart-summary">
        <p>Общо тегло <strong>${escapeHtml(data.totalWeight)}</strong></p>
        <p class="store-cart-total">Общо ${escapeHtml(data.total)}</p>
      </div>
      <a href="/checkout">Към детайли за поръчката</a>
    </div>`;
}

export function mountCartPage() {
  const page = document.querySelector('[data-cart-page]');
  const body = page?.querySelector('[data-cart-page-body]');
  if (!(page instanceof HTMLElement) || !(body instanceof HTMLElement)) return;

  const csrf = page.dataset.csrf ?? '';
  const render = (data) => {
    body.innerHTML = cartContent(data, csrf);
    page.classList.toggle('is-empty', !Array.isArray(data?.lines) || data.lines.length === 0);
  };

  body.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-cart-action]');
    if (!(form instanceof HTMLFormElement)) return;
    event.preventDefault();

    const buttons = [...form.querySelectorAll('button')];
    buttons.forEach((button) => { button.disabled = true; });

    try {
      const submitter = event.submitter;
      const formData = new FormData(form);
      if (submitter?.name) formData.set(submitter.name, submitter.value);
      const response = await fetch(form.action, { method: 'POST', headers: { Accept: 'application/json' }, body: formData });
      const result = await response.json();
      if (!response.ok) throw new Error(result?.message || 'Количката не може да бъде обновена.');
      render(result.data);
      window.dispatchEvent(new CustomEvent('store:cart-state', { detail: { data: result.data } }));
    } catch (error) {
      notify(error instanceof Error ? error.message : 'Количката не може да бъде обновена.', 'error');
      buttons.forEach((button) => { button.disabled = false; });
    }
  });

  window.addEventListener('store:cart-updated', (event) => {
    if (event.detail?.data) render(event.detail.data);
  });

  window.addEventListener('store:cart-state', (event) => {
    if (event.detail?.data) render(event.detail.data);
  });
}
