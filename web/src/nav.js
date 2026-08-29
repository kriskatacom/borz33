const moneyFormatter = new Intl.NumberFormat('bg-BG', {
  style: 'currency',
  currency: 'BGN',
});

function formatPrice(value) {
  const amount = typeof value === 'number' ? value : Number(value);

  if (!Number.isFinite(amount)) {
    return '';
  }

  return moneyFormatter.format(amount);
}

export function registerStoreHeader(Alpine) {
  document.addEventListener('alpine:init', () => {
    Alpine.data('storeHeader', (initialQuery = '') => ({
      menuOpen: false,
      accountOpen: false,
      openCat: 0,
      closeTimer: null,
      searchOpen: false,
      searchQuery: typeof initialQuery === 'string' ? initialQuery : '',
      searchItems: [],
      searchFeatured: true,
      searchLoading: false,
      searchAbort: null,
      featuredCache: null,

      openCategory(id) {
        clearTimeout(this.closeTimer);
        this.openCat = id;
        this.accountOpen = false;
        this.searchOpen = false;
      },

      delayCloseCategory() {
        clearTimeout(this.closeTimer);
        this.closeTimer = setTimeout(() => {
          this.openCat = 0;
        }, 180);
      },

      toggleCategory(id) {
        clearTimeout(this.closeTimer);
        this.openCat = this.openCat === id ? 0 : id;
        this.accountOpen = false;
        this.searchOpen = false;
      },

      toggleAccount() {
        this.accountOpen = !this.accountOpen;
        this.menuOpen = false;
        this.openCat = 0;
        this.searchOpen = false;
      },

      toggleMenu() {
        this.menuOpen = !this.menuOpen;
        this.accountOpen = false;
        this.openCat = 0;
        this.searchOpen = false;
      },

      closeAll() {
        clearTimeout(this.closeTimer);
        this.menuOpen = false;
        this.accountOpen = false;
        this.openCat = 0;
        this.searchOpen = false;
      },

      openSearch() {
        this.searchOpen = true;
        this.accountOpen = false;
        this.openCat = 0;
        void this.loadSearch();
      },

      closeSearch() {
        if (this.$refs.searchInput === document.activeElement) {
          this.searchOpen = true;
          return;
        }

        this.searchOpen = false;
      },

      onSearchInput() {
        this.searchOpen = true;
        void this.loadSearch();
      },

      formatPrice,

      async loadSearch() {
        const q = this.searchQuery.trim();

        if (q === '' && Array.isArray(this.featuredCache)) {
          this.searchItems = this.featuredCache;
          this.searchFeatured = true;
          this.searchLoading = false;
          return;
        }

        this.searchAbort?.abort();
        this.searchAbort = new AbortController();
        this.searchLoading = true;

        try {
          const response = await fetch('/search/products?q=' + encodeURIComponent(q), {
            headers: { Accept: 'application/json' },
            signal: this.searchAbort.signal,
          });

          if (!response.ok) {
            throw new Error('search failed');
          }

          const body = await response.json();
          const products = Array.isArray(body?.data?.products) ? body.data.products : [];
          const featured = body?.data?.featured === true;

          this.searchItems = products;
          this.searchFeatured = featured;

          if (featured) {
            this.featuredCache = products;
          }
        } catch (error) {
          if (error instanceof DOMException && error.name === 'AbortError') {
            return;
          }

          this.searchItems = [];
          this.searchFeatured = q === '';
        } finally {
          this.searchLoading = false;
        }
      },
    }));
  });
}
