function updateDeliveryCopy(root) {
  const method = root.querySelector('input[name="delivery_method"]:checked')?.value ?? 'address';
  const label = root.querySelector('[data-address-label]');
  const input = root.querySelector('[data-address-input]');
  const hint = root.querySelector('[data-address-hint]');

  if (!label || !input || !hint) {
    return;
  }

  const office = method === 'office';
  label.firstChild.textContent = office ? 'Офис на куриер ' : 'Улица и номер ';
  input.placeholder = office
    ? 'Напр. Еконт Център, офис 1234'
    : 'Улица, номер, вход, етаж и апартамент';
  input.autocomplete = office ? 'off' : 'street-address';
  hint.textContent = office
    ? 'Посочете куриер, име или код на офиса.'
    : 'Добавете вход, етаж и апартамент, ако са приложими.';
}

function clearServerError(field) {
  const wrapper = field.type === 'radio'
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

  root.querySelectorAll('input[name="delivery_method"]').forEach((input) => {
    input.addEventListener('change', () => updateDeliveryCopy(root));
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
