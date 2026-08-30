<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { notify } from '../toast.js';

const props = defineProps({
  initialCount: { type: Number, default: 0 },
  csrf: { type: String, required: true },
  active: { type: Boolean, default: false },
});

const count = ref(props.initialCount);
const lines = ref([]);
const total = ref('');
const totalWeight = ref('');
const open = ref(false);
const loading = ref(false);
const loaded = ref(false);
const error = ref('');
const announcement = ref('');
const empty = computed(() => loaded.value && lines.value.length === 0);

function applyCart(data) {
  count.value = Number(data?.count ?? 0);
  lines.value = Array.isArray(data?.lines) ? data.lines : [];
  total.value = typeof data?.total === 'string' ? data.total : '';
  totalWeight.value = typeof data?.totalWeight === 'string' ? data.totalWeight : '';
  loaded.value = true;
}

async function request(url, options = {}) {
  loading.value = true;
  error.value = '';

  try {
    const response = await fetch(url, {
      ...options,
      headers: { Accept: 'application/json', ...(options.headers ?? {}) },
    });
    const body = await response.json();

    if (!response.ok) {
      throw new Error(body?.message || 'Количката не може да бъде обновена.');
    }

    applyCart(body?.data);
    window.dispatchEvent(new CustomEvent('store:cart-state', {
      detail: { data: body?.data },
    }));

    if (options.method) {
      notify('Количката е обновена.');
    }
  } catch (reason) {
    error.value = reason instanceof Error ? reason.message : 'Възникна грешка.';
    notify(error.value, 'error');
  } finally {
    loading.value = false;
  }
}

async function showCart() {
  open.value = true;
  document.documentElement.classList.add('is-cart-open');

  if (!loaded.value) {
    await request('/cart/data');
  }
}

function closeCart() {
  open.value = false;
  document.documentElement.classList.remove('is-cart-open');
}

function postLine(line, qty = null) {
  const body = new URLSearchParams({ _token: props.csrf });
  let url = `/cart/${line.index}/delete`;

  if (qty !== null) {
    url = `/cart/${line.index}`;
    body.set('qty', String(qty));
  }

  return request(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
    body,
  });
}

function onCartUpdated(event) {
  applyCart(event.detail?.data);
  announcement.value = event.detail?.message || 'Количката е обновена.';
  void showCart();
}

function onKeydown(event) {
  if (event.key === 'Escape' && open.value) {
    closeCart();
  }
}

onMounted(() => {
  window.addEventListener('store:cart-updated', onCartUpdated);
  window.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
  window.removeEventListener('store:cart-updated', onCartUpdated);
  window.removeEventListener('keydown', onKeydown);
  document.documentElement.classList.remove('is-cart-open');
});
</script>

<template>
  <span class="relative inline-flex">
    <button
      type="button"
      class="store-icon-btn"
      :class="{ 'is-active': active }"
      aria-label="Отвори количката"
      :aria-expanded="open"
      aria-controls="store-cart-drawer"
      @click="showCart"
    >
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
      <span v-if="count > 0" class="store-icon-badge">{{ count }}</span>
    </button>
  </span>

  <Teleport to="body">
    <Transition name="store-cart-fade">
      <div v-if="open" class="store-cart-overlay" @click.self="closeCart">
        <aside id="store-cart-drawer" class="store-cart-drawer" aria-label="Количка" aria-modal="true" role="dialog">
          <header class="store-cart-drawer-head">
            <div>
              <p class="store-cart-drawer-eyebrow">Вашата поръчка</p>
              <h2>Количка <span v-if="count">({{ count }})</span></h2>
            </div>
            <button type="button" class="store-cart-drawer-close" aria-label="Затвори количката" @click="closeCart">×</button>
          </header>

          <p class="sr-only" aria-live="polite">{{ announcement }}</p>
          <p v-if="error" class="store-cart-drawer-error" role="alert">{{ error }}</p>
          <p v-if="loading && !loaded" class="store-cart-drawer-state">Зареждане…</p>
          <div v-else-if="empty" class="store-cart-drawer-empty">
            <span class="store-cart-drawer-empty-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
            </span>
            <h3>Количката е празна</h3>
            <p>Добавете продукт и той ще се появи тук.</p>
            <a href="/catalog" class="store-cart-drawer-empty-link">Разгледайте продуктите</a>
          </div>

          <ul v-else class="store-cart-drawer-list" :class="{ 'is-loading': loading }">
            <li v-for="line in lines" :key="`${line.product_id}-${line.variant_id}-${line.index}`" class="store-cart-drawer-item">
              <a :href="line.href" class="store-cart-drawer-thumb">
                <img v-if="line.image" :src="line.image" :alt="line.alt" width="80" height="100">
              </a>
              <div class="store-cart-drawer-info">
                <a :href="line.href" class="store-cart-drawer-name">{{ line.name }}</a>
                <p v-if="line.options" class="store-cart-drawer-meta">{{ line.options }}</p>
                <p class="store-cart-drawer-meta">Тегло: <strong>{{ line.weight }}</strong> · общо: <strong>{{ line.total_weight }}</strong></p>
                <div class="store-cart-drawer-row">
                  <div class="store-cart-drawer-qty" aria-label="Количество">
                    <button type="button" :disabled="loading" aria-label="Намали" @click="postLine(line, line.qty - 1)">−</button>
                    <span>{{ line.qty }}</span>
                    <button type="button" :disabled="loading" aria-label="Увеличи" @click="postLine(line, line.qty + 1)">+</button>
                  </div>
                  <strong>{{ new Intl.NumberFormat('bg-BG', { style: 'currency', currency: 'EUR' }).format(Number(line.total)) }}</strong>
                </div>
                <button type="button" class="store-cart-drawer-remove" :disabled="loading" @click="postLine(line)">Премахни</button>
              </div>
            </li>
          </ul>

          <footer v-if="loaded && lines.length" class="store-cart-drawer-foot">
            <p class="store-cart-drawer-weight"><span>Общо тегло</span><strong>{{ totalWeight }}</strong></p>
            <p><span>Общо</span><strong>{{ total }}</strong></p>
            <div class="store-cart-drawer-actions">
              <a href="/checkout" class="store-cart-drawer-link">Към поръчка</a>
              <a href="/cart" class="store-cart-drawer-secondary">Преглед на количката</a>
            </div>
          </footer>
        </aside>
      </div>
    </Transition>
  </Teleport>
</template>
