const money = new Intl.NumberFormat('bg-BG', { style: 'currency', currency: 'EUR' });

export function mountFreeShippingNotice() {
  const root = document.querySelector('[data-free-shipping-notice]');
  const message = root?.querySelector('[data-free-shipping-message]');
  const action = root?.querySelector('[data-free-shipping-action]');

  if (!root || !message || !action) return;

  const threshold = Math.max(0, Number(root.dataset.threshold ?? 0));

  function render(subtotal) {
    const current = Math.max(0, Number(subtotal ?? 0));
    const eligible = current > threshold;
    root.classList.toggle('is-unlocked', eligible);

    if (eligible) {
      message.textContent = 'Поздравления — отключихте безплатна доставка!';
      action.textContent = 'Към количката';
      root.querySelector('a')?.setAttribute('href', '/cart');
      return;
    }

    root.querySelector('a')?.setAttribute('href', '/catalog');
    action.textContent = 'Разгледайте продуктите';

    if (current <= 0) {
      message.textContent = `Безплатна доставка за поръчки над ${money.format(threshold)}.`;
      return;
    }

    const remaining = Math.max(0.01, Math.round((threshold + 0.01 - current) * 100) / 100);
    message.textContent = `Добавете още ${money.format(remaining)}, за да отключите безплатна доставка.`;
  }

  function onCart(event) {
    if (typeof event.detail?.data?.subtotal === 'number') render(event.detail.data.subtotal);
  }

  render(Number(root.dataset.subtotal ?? 0));
  window.addEventListener('store:cart-updated', onCart);
  window.addEventListener('store:cart-state', onCart);
}
