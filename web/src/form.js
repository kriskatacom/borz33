function flattenErrors(errors) {
  if (!errors || typeof errors !== 'object') {
    return {};
  }

  const map = {};

  for (const [key, value] of Object.entries(errors)) {
    if (Array.isArray(value)) {
      map[key] = typeof value[0] === 'string' ? value[0] : '';
    } else if (typeof value === 'string') {
      map[key] = value;
    }
  }

  return map;
}

function trimValue(value) {
  return typeof value === 'string' ? value.trim() : '';
}

function digits(value) {
  return String(value ?? '').replace(/\D+/g, '');
}

export function registerStoreForm(Alpine) {
  registerStoreSubmitStates();

  document.addEventListener('alpine:init', () => {
    Alpine.data('storeAccountForm', (config = {}) => ({
      busy: false,
      kind: typeof config.kind === 'string' ? config.kind : 'profile',
      party: typeof config.party === 'string' ? config.party : 'person',
      idleLabel: typeof config.idleLabel === 'string' ? config.idleLabel : '',
      showCurrent: false,
      showNew: false,
      showConfirm: false,
      errors: flattenErrors(config.errors),
      touched: Object.fromEntries(Object.keys(flattenErrors(config.errors)).map((key) => [key, true])),

      initPhone() {
        const phone = this.read('phone').trim();
        const country = this.$refs.phoneCountry;
        const number = this.$refs.phoneNumber;

        if (!(country instanceof HTMLSelectElement) || !(number instanceof HTMLInputElement)) {
          return;
        }

        const option = [...country.options]
          .sort((a, b) => b.value.length - a.value.length)
          .find((item) => phone.startsWith(item.value));

        if (option) {
          country.value = option.value;
          number.value = phone.slice(option.value.length).trim();
        }
      },

      syncPhone() {
        const country = this.$refs.phoneCountry;
        const number = this.$refs.phoneNumber;
        const hidden = this.$el.elements?.namedItem('phone');

        if (!(country instanceof HTMLSelectElement) || !(number instanceof HTMLInputElement) || !(hidden instanceof HTMLInputElement)) {
          return;
        }

        const local = number.value.trim();
        hidden.value = local === '' ? '' : `${country.value} ${local}`;
        hidden.dispatchEvent(new Event('input', { bubbles: true }));
        this.onInput('phone');
      },

      error(name) {
        return this.errors[name] || '';
      },

      invalid(name) {
        return Boolean(this.errors[name]);
      },

      setFieldError(name, message) {
        const next = { ...this.errors };

        if (message) {
          next[name] = message;
        } else {
          delete next[name];
        }

        this.errors = next;
      },

      setParty(value) {
        this.party = value === 'company' ? 'company' : 'person';
        this.clearHiddenPartyErrors();
      },

      toggleParty() {
        this.setParty(this.party === 'company' ? 'person' : 'company');
      },

      read(name) {
        const form = this.$el instanceof HTMLFormElement ? this.$el : this.$el.closest('form');
        const root = form ?? this.$el;
        let field = null;

        if (root instanceof HTMLFormElement) {
          const named = root.elements.namedItem(name);

          if (named instanceof RadioNodeList) {
            field = [...named].find((item) => item instanceof HTMLInputElement && item.checked) ?? named[0];
          } else {
            field = named;
          }
        }

        if (!(field instanceof HTMLElement)) {
          field = root.querySelector(`[name="${CSS.escape(name)}"]`);
        }

        if (
          !(field instanceof HTMLInputElement) &&
          !(field instanceof HTMLSelectElement) &&
          !(field instanceof HTMLTextAreaElement)
        ) {
          return '';
        }

        if (field instanceof HTMLInputElement && field.type === 'radio') {
          const checked = root.querySelector(`[name="${CSS.escape(name)}"]:checked`);

          return checked instanceof HTMLInputElement ? checked.value : this.party;
        }

        if (field instanceof HTMLInputElement && field.type === 'checkbox') {
          return field.checked;
        }

        return field.value ?? '';
      },

      onInput(name) {
        if (name === 'party') {
          this.party = this.read('party') || 'person';
          this.clearHiddenPartyErrors();
        }

        if (this.touched[name] || this.errors[name]) {
          this.validate(name);
        }

        if (name === 'password' && (this.touched.password_confirmation || this.errors.password_confirmation)) {
          this.validate('password_confirmation');
        }
      },

      onBlur(name) {
        this.touched[name] = true;

        if (this.kind === 'address' && !this.names().includes(name)) {
          this.setFieldError(name, '');

          return;
        }

        this.validate(name);
      },

      onSubmit(event) {
        if (this.busy) {
          event.preventDefault();

          return;
        }

        let ok = true;

        for (const name of this.names()) {
          this.touched[name] = true;
          this.validate(name);

          if (this.errors[name]) {
            ok = false;
          }
        }

        if (!ok) {
          event.preventDefault();
          this.$el.querySelector('.is-invalid')?.focus();

          return;
        }

        this.busy = true;
      },

      names() {
        if (this.kind === 'password') {
          return ['current_password', 'password', 'password_confirmation'];
        }

        if (this.kind === 'address') {
          const shared = ['label', 'line1', 'city', 'postal_code', 'country'];

          if (this.party === 'company') {
            return [...shared, 'company_name', 'eik', 'vat_number', 'mol'];
          }

          return [...shared, 'first_name', 'last_name'];
        }

        if (this.kind === 'theme') {
          return [];
        }

        return ['first_name', 'last_name', 'phone'];
      },

      clearHiddenPartyErrors() {
        const hidden =
          this.party === 'company'
            ? ['first_name', 'last_name']
            : ['company_name', 'eik', 'vat_number', 'mol'];

        for (const name of hidden) {
          this.setFieldError(name, '');
        }
      },

      validate(name) {
        this.setFieldError(name, this.message(name));
      },

      message(name) {
        const value = this.read(name);
        const text = trimValue(typeof value === 'string' ? value : '');

        if (name === 'first_name' || name === 'last_name' || name === 'company_name' || name === 'mol' || name === 'line1' || name === 'city' || name === 'country') {
          const labels = {
            first_name: 'име',
            last_name: 'фамилия',
            company_name: 'име на фирмата',
            mol: 'МОЛ',
            line1: 'адрес',
            city: 'град',
            country: 'държава',
          };

          if (text === '') {
            return `Полето ${labels[name]} е задължително.`;
          }

          const max = name === 'company_name' || name === 'mol' || name === 'line1' ? 191 : name === 'country' ? 80 : 100;

          if (text.length > max) {
            return `Полето ${labels[name]} трябва да бъде най-много ${max} символа.`;
          }

          return '';
        }

        if (name === 'label') {
          if (text.length > 80) {
            return 'Полето име на адреса трябва да бъде най-много 80 символа.';
          }

          return '';
        }

        if (name === 'phone') {
          if (text === '') {
            return '';
          }

          if (text.length > 32) {
            return 'Полето телефон трябва да бъде най-много 32 символа.';
          }

          if (digits(text).length < 8) {
            return 'Въведете валиден телефонен номер.';
          }

          return '';
        }

        if (name === 'eik') {
          const eik = digits(value);

          if (eik === '') {
            return 'Полето ЕИК е задължително.';
          }

          if (!/^\d{9}$/.test(eik)) {
            return 'ЕИК трябва да е 9 цифри.';
          }

          return '';
        }

        if (name === 'vat_number') {
          if (text === '') {
            return '';
          }

          if (!/^BG\d{9,10}$/i.test(text.replace(/\s+/g, ''))) {
            return 'ДДС номерът трябва да е във формат BG и 9 или 10 цифри.';
          }

          return '';
        }

        if (name === 'postal_code') {
          if (text === '') {
            return 'Полето пощенски код е задължително.';
          }

          if (!/^\d{4}$/.test(text.replace(/\s+/g, ''))) {
            return 'Пощенският код трябва да е 4 цифри.';
          }

          return '';
        }

        if (name === 'current_password') {
          return text === '' ? 'Полето текуща парола е задължително.' : '';
        }

        if (name === 'password') {
          if (text === '') {
            return 'Полето парола е задължително.';
          }

          if (text.length < 8) {
            return 'Полето парола трябва да бъде поне 8 символа.';
          }

          return '';
        }

        if (name === 'password_confirmation') {
          if (text === '') {
            return 'Полето потвърждение на паролата е задължително.';
          }

          if (text !== this.read('password')) {
            return 'Паролите не съвпадат.';
          }

          return '';
        }

        return '';
      },
    }));
  });
}

function registerStoreSubmitStates() {
  const selector = 'form button[type="submit"]';
  const excluded = (button) => button.closest('[data-cart-action], [data-account-message-reply], [data-review-submit], [data-checkout-submit], [action="/logout"]');

  document.querySelectorAll(selector).forEach((button) => {
    if (!(button instanceof HTMLButtonElement) || excluded(button)) return;
    button.classList.add('store-submit');
  });

  document.addEventListener('submit', (event) => {
    if (event.defaultPrevented) return;

    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    const button = event.submitter instanceof HTMLButtonElement
      ? event.submitter
      : form.querySelector('button[type="submit"]');

    if (!(button instanceof HTMLButtonElement) || excluded(button)) return;

    button.classList.add('store-submit', 'is-loading');
    button.setAttribute('aria-busy', 'true');
    button.disabled = true;
    button.replaceChildren(
      Object.assign(document.createElement('span'), { className: 'store-submit-spinner', ariaHidden: 'true' }),
      document.createTextNode(button.dataset.loadingLabel || 'Изпращане…'),
    );
  });
}
