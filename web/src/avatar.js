export function registerStoreAvatar(Alpine) {
  document.addEventListener('alpine:init', () => {
    Alpine.data('storeAvatar', (config = {}) => ({
      csrf: typeof config.csrf === 'string' ? config.csrf : '',
      url: config.url ?? null,
      initials: typeof config.initials === 'string' ? config.initials : '',
      presets: Array.isArray(config.presets) ? config.presets : [],
      open: false,
      busy: false,
      error: '',

      toggle() {
        this.open = !this.open;
        this.error = '';
      },

      pickFile() {
        this.$refs.file?.click();
      },

      async applyPreset(id) {
        const form = new FormData();
        form.append('preset', id);
        await this.submit('/account/avatar', form);
      },

      async onFile(event) {
        const file = event.target.files?.[0];
        event.target.value = '';

        if (!file) {
          return;
        }

        const form = new FormData();
        form.append('image', file);
        await this.submit('/account/avatar', form);
      },

      async remove() {
        const form = new FormData();
        await this.submit('/account/avatar/delete', form);
      },

      async submit(url, form) {
        if (this.busy) {
          return;
        }

        this.busy = true;
        this.error = '';
        form.append('_token', this.csrf);

        try {
          const response = await fetch(url, {
            method: 'POST',
            body: form,
            headers: { Accept: 'application/json' },
          });
          const body = await response.json().catch(() => null);

          if (!response.ok) {
            const errors = body?.errors;
            const first = errors && typeof errors === 'object' ? Object.values(errors)[0] : null;
            this.error =
              (Array.isArray(first) ? first[0] : first) ||
              body?.message ||
              'Снимката не можа да се запише.';
            return;
          }

          this.url = body?.data?.avatar_url ?? null;
        } catch {
          this.error = 'Снимката не можа да се запише.';
        } finally {
          this.busy = false;
        }
      },
    }));
  });
}
