import { createApp, h } from 'vue';
import { Toaster, toast } from 'vue-sonner';
import 'vue-sonner/style.css';

export function notify(message, type = 'success') {
  const method = typeof toast[type] === 'function' ? toast[type] : toast;
  method(message);
}

export function mountStoreToasts() {
  const host = document.createElement('div');
  host.id = 'store-toast-app';
  document.body.append(host);

  createApp({
    render: () => h(Toaster, {
      position: 'top-right',
      richColors: true,
      closeButton: true,
      theme: document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light',
    }),
  }).mount(host);

  window.addEventListener('store:toast', (event) => {
    notify(event.detail?.message || '', event.detail?.type || 'success');
  });
}
