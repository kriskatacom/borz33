import { createApp } from 'vue';
import StoreCart from './components/StoreCart.vue';

export function mountStoreCart() {
  const element = document.querySelector('#store-cart-app');

  if (!element) {
    return;
  }

  createApp(StoreCart, {
    initialCount: Number(element.dataset.count ?? 0),
    csrf: element.dataset.csrf ?? '',
    active: element.dataset.active === 'true',
  }).mount(element);
}
