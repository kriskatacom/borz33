const STORE_THEME_KEY = 'borz33-store-theme';

function isTheme(value) {
  return value === 'light' || value === 'dark' || value === 'system';
}

function resolvedTheme(preference) {
  if (preference === 'dark' || preference === 'light') {
    return preference;
  }

  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function applyDocumentTheme(preference) {
  const resolved = resolvedTheme(preference);
  const root = document.documentElement;

  root.dataset.theme = resolved;
  root.dataset.themePreference = preference;
  root.style.colorScheme = resolved;

  const meta = document.querySelector('meta[name="theme-color"]');

  if (meta) {
    meta.setAttribute('content', resolved === 'dark' ? '#0a0a0a' : '#ffffff');
  }
}

export function registerThemeStore(Alpine) {
  document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
      preference: 'system',

      init() {
        const server = window.STORE_THEME;
        this.preference = isTheme(server) ? server : 'system';
        applyDocumentTheme(this.preference);

        if (isTheme(server)) {
          localStorage.setItem(STORE_THEME_KEY, server);
        } else {
          localStorage.removeItem(STORE_THEME_KEY);
        }

        const media = window.matchMedia('(prefers-color-scheme: dark)');
        media.addEventListener('change', () => {
          if (this.preference === 'system') {
            applyDocumentTheme('system');
          }
        });
      },

      set(preference) {
        if (!isTheme(preference)) {
          return;
        }

        this.preference = preference;
        localStorage.setItem(STORE_THEME_KEY, preference);
        applyDocumentTheme(preference);
      },
    });
  });
}
