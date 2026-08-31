function updateDeliveryCopy(root, resetSelection = false) {
  const method = root.querySelector('input[name="delivery_method"]:checked')?.value ?? 'address';
  const label = root.querySelector('[data-address-label]');
  const input = root.querySelector('[data-address-input]');
  const hint = root.querySelector('[data-address-hint]');
  const officeButton = root.querySelector('[data-econt-office-open]');
  const officeCode = root.querySelector('[data-econt-office-code]');

  if (!label || !input || !hint) {
    return;
  }

  const pickup = method === 'office';
  label.firstChild.textContent = pickup ? 'Офис на куриер ' : 'Улица и номер ';
  input.placeholder = pickup ? 'Изберете офис от картата' : 'Улица, номер, вход, етаж и апартамент';
  input.autocomplete = pickup ? 'off' : 'street-address';
  input.readOnly = pickup;
  officeButton?.toggleAttribute('hidden', !pickup);
  if (officeButton) officeButton.textContent = 'Избери офис на Еконт';
  hint.textContent = pickup ? 'Избраният офис определя точната цена на доставката.' : 'Добавете вход, етаж и апартамент, ако са приложими.';

  if (resetSelection) {
    input.value = '';

    if (officeCode) {
      officeCode.value = '';
    }
  }
}

function clearServerError(field) {
  const wrapper = field.name === 'accept_terms'
    ? field.closest('.store-checkout-legal')
    : field.type === 'radio'
      ? field.closest('fieldset')
      : field.closest('label');

  if (!wrapper?.classList.contains('has-error')) {
    return;
  }

  wrapper.classList.remove('has-error');
  field.removeAttribute('aria-invalid');
  const errorId = field.getAttribute('aria-describedby');

  if (errorId) {
    document.getElementById(errorId)?.remove();
    field.removeAttribute('aria-describedby');
  }

  wrapper.querySelector('.store-checkout-group-error')?.remove();
}

export function mountStoreCheckout() {
  const root = document.querySelector('[data-checkout]');
  const form = root?.querySelector('[data-checkout-form]');

  if (!root || !form) {
    return;
  }

  const shippingPrice = root.querySelector('[data-shipping-price]');
  const shippingStatus = root.querySelector('[data-shipping-status]');
  const shippingMessage = root.querySelector('[data-shipping-message]');
  const grandTotal = root.querySelector('[data-checkout-grand-total]');
  const quoteButton = root.querySelector('[data-shipping-quote]');
  const officeDialog = root.querySelector('[data-econt-office-dialog]');
  const officeFrame = root.querySelector('[data-econt-office-frame]');
  const officeCode = root.querySelector('[data-econt-office-code]');
  const addressInput = root.querySelector('[data-address-input]');
  const invoiceToggle = root.querySelector('[data-invoice-toggle]');
  const invoiceFields = root.querySelector('[data-invoice-fields]');
  let quoteTimer;
  let quoteRequest;

  function updateInvoiceFields() {
    if (!invoiceToggle || !invoiceFields) return;
    invoiceFields.hidden = !invoiceToggle.checked;
    invoiceFields.querySelectorAll('input').forEach((input) => {
      input.required = invoiceToggle.checked && input.name !== 'invoice_vat_number';
      input.disabled = !invoiceToggle.checked;
    });
  }
  invoiceToggle?.addEventListener('change', updateInvoiceFields);
  updateInvoiceFields();

  function invalidateQuote(message = 'Попълнете данните, за да получите актуална цена от Еконт.') {
    quoteRequest?.abort();
    shippingPrice.textContent = 'Не е изчислена';
    shippingMessage.textContent = message;
    shippingStatus.classList.remove('has-error');
    grandTotal.textContent = grandTotal.dataset.productsTotal;
  }

  function scheduleQuote() {
    window.clearTimeout(quoteTimer);
    invalidateQuote('Цената ще се преизчисли след попълване на данните.');
    quoteTimer = window.setTimeout(() => void requestQuote(false), 650);
  }

  root.querySelectorAll('input[name="delivery_method"]').forEach((input) => {
    input.addEventListener('change', () => {
      updateDeliveryCopy(root, true);
      scheduleQuote();
    });
  });

  async function requestQuote(showErrors = true) {
    const data = new FormData(form);
    const method = String(data.get('delivery_method') ?? 'address');

    if (!['address', 'office'].includes(method)) {
      if (showErrors) {
        invalidateQuote('Изберете начин на доставка.');
      }
      return;
    }

    quoteRequest?.abort();
    const controller = new AbortController();
    quoteRequest = controller;
    quoteButton.disabled = true;
    quoteButton.textContent = 'Изчисляване…';
    shippingPrice.textContent = 'Изчисляване…';
    shippingStatus.classList.remove('has-error');

    try {
      const response = await fetch('/checkout/shipping-quote', {
        method: 'POST',
        body: data,
        headers: { Accept: 'application/json' },
        signal: controller.signal,
      });
      const payload = await response.json().catch(() => ({}));

      if (!response.ok) {
        throw new Error(payload.message || 'Цената за доставка не можа да се изчисли.');
      }

      shippingPrice.textContent = payload.data.formatted;
      grandTotal.textContent = payload.data.grand_total_formatted;
      const payer = String(data.get('shipping_payer') ?? 'receiver');
      shippingMessage.textContent = payer === 'sender'
        ? `Магазинът поема изчислената от Econt доставка (${payload.data.carrier_formatted}).`
        : `Цена от ${payload.data.environment === 'production' ? 'Production' : 'Demo'} средата на Econt${payload.data.expected_delivery_date ? ` · очаквана доставка ${payload.data.expected_delivery_date}` : ''}.`;
    } catch (error) {
      if (error.name === 'AbortError') {
        return;
      }

      shippingPrice.textContent = 'Недостъпна';
      shippingMessage.textContent = error.message;
      shippingStatus.classList.add('has-error');
      grandTotal.textContent = grandTotal.dataset.productsTotal;
    } finally {
      if (quoteRequest === controller) {
        quoteButton.disabled = false;
        quoteButton.textContent = 'Преизчисли доставката';
      }
    }
  }

  quoteButton?.addEventListener('click', () => void requestQuote(true));

  root.querySelectorAll('input[name="shipping_payer"], input[name="payment_method"]').forEach((input) => {
    input.addEventListener('change', scheduleQuote);
  });
  root.querySelectorAll('input[name="first_name"], input[name="last_name"], input[name="phone"], input[name="city"], input[name="postal_code"], input[name="address_line"]').forEach((input) => {
    input.addEventListener('input', scheduleQuote);
  });

  root.querySelector('[data-econt-office-open]')?.addEventListener('click', () => {
    const city = form.elements.city?.value ?? '';
    const method = form.elements.delivery_method?.value ?? 'office';
    const params = new URLSearchParams({
      lang: 'bg',
      shopUrl: window.location.origin,
      officeType: 'office',
    });

    if (city.trim() !== '') {
      params.set('city', city.trim());
    }

    const locatorUrl = root.dataset.econtLocatorUrl;
    if (!locatorUrl) return;
    officeFrame.src = `${locatorUrl}/?${params.toString()}`;
    officeDialog.showModal();
  });

  root.querySelector('[data-econt-office-close]')?.addEventListener('click', () => officeDialog.close());
  officeDialog?.addEventListener('click', (event) => {
    if (event.target === officeDialog) {
      officeDialog.close();
    }
  });

  window.addEventListener('message', (event) => {
    const locatorOrigin = root.dataset.econtLocatorUrl ? new URL(root.dataset.econtLocatorUrl).origin : '';
    if (event.origin !== locatorOrigin || !event.data?.office || !officeDialog?.open) {
      return;
    }

    const office = event.data.office;
    const city = office.address?.city;

    if (!office.code || !city?.name) {
      return;
    }

    const selectedMethod = form.elements.delivery_method?.value ?? root.querySelector('input[name="delivery_method"]:checked')?.value;
    if (selectedMethod === 'office' && office.isAPS) {
      invalidateQuote('Изберете стандартен офис на Еконт.');
      return;
    }
    officeCode.value = String(office.code);
    addressInput.value = `${office.name || 'Еконт'} — ${office.address?.fullAddress || `офис ${office.code}`}`.slice(0, 191);
    form.elements.city.value = city.name;
    form.elements.postal_code.value = city.postCode || '';
    clearServerError(addressInput);
    officeDialog.close();
    void requestQuote(true);
  });

  const notes = root.querySelector('[data-notes]');
  const notesCount = root.querySelector('[data-notes-count]');

  notes?.addEventListener('input', () => {
    if (notesCount) {
      notesCount.textContent = String(notes.value.length);
    }
  });

  form.querySelectorAll('input, textarea').forEach((field) => {
    field.addEventListener('input', () => clearServerError(field), { once: true });
    field.addEventListener('change', () => clearServerError(field), { once: true });
  });

  updateDeliveryCopy(root);
  void requestQuote(false);

  form.addEventListener('submit', () => {
    const button = form.querySelector('[data-checkout-submit]');
    const label = form.querySelector('[data-submit-label]');
    const loading = form.querySelector('[data-submit-loading]');

    if (!button || button.disabled) {
      return;
    }

    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    label.hidden = true;
    loading.hidden = false;
  });

  const invalidField = form.querySelector('[aria-invalid="true"]');

  if (invalidField) {
    requestAnimationFrame(() => {
      invalidField.focus({ preventScroll: true });
      invalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  }
}
