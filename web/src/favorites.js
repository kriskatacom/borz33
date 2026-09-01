import { notify } from './toast.js';

function csrfToken() {
  return document.querySelector('#store-cart-app')?.dataset.csrf ?? '';
}

function updateButtons(productId, favorite) {
  document.querySelectorAll(`[data-favorite-product="${productId}"]`).forEach((button) => {
    button.dataset.favorite = String(favorite);
    button.setAttribute('aria-pressed', String(favorite));

    const label = button.querySelector('span');
    if (label) {
      label.textContent = favorite ? 'В любими' : 'Добави в любими';
    }

    button.setAttribute('aria-label', favorite ? 'Премахни от любими' : 'Добави в любими');
  });
}

function updateCount(count) {
  document.querySelectorAll('[data-favorite-count]').forEach((badge) => {
    badge.textContent = String(count);
    badge.hidden = count < 1;
  });
}

function updateFavoritesPage(productId, favorite) {
  if (!favorite) {
    document.querySelector(`[data-favorite-card="${productId}"]`)?.remove();
  }

}

export function registerStoreFavorites() {
  document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-favorite-product]');

    if (!(button instanceof HTMLButtonElement) || button.disabled) {
      return;
    }

    const productId = Number(button.dataset.favoriteProduct);
    if (!Number.isInteger(productId) || productId < 1) {
      return;
    }

    button.disabled = true;

    try {
      const body = new URLSearchParams({ _token: csrfToken() });
      const response = await fetch(`/favorites/${productId}/toggle`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
        },
        body,
      });
      const payload = await response.json();

      if (!response.ok) {
        throw new Error(payload?.message || 'Любими не може да бъде обновено.');
      }

      const favorite = payload?.data?.favorite === true;
      updateButtons(productId, favorite);
      updateCount(Number(payload?.data?.count ?? 0));
      updateFavoritesPage(productId, favorite);
      notify(payload.message);
    } catch (reason) {
      notify(reason instanceof Error ? reason.message : 'Възникна грешка.', 'error');
    } finally {
      button.disabled = false;
    }
  });
}
