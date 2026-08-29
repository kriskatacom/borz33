export function registerStoreHeader(Alpine) {
  document.addEventListener('alpine:init', () => {
    Alpine.data('storeHeader', () => ({
      menuOpen: false,
      accountOpen: false,
      openCat: 0,
      closeTimer: null,

      openCategory(id) {
        clearTimeout(this.closeTimer);
        this.openCat = id;
        this.accountOpen = false;
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
      },

      toggleAccount() {
        this.accountOpen = !this.accountOpen;
        this.menuOpen = false;
        this.openCat = 0;
      },

      toggleMenu() {
        this.menuOpen = !this.menuOpen;
        this.accountOpen = false;
        this.openCat = 0;
      },

      closeAll() {
        clearTimeout(this.closeTimer);
        this.menuOpen = false;
        this.accountOpen = false;
        this.openCat = 0;
      },
    }));
  });
}
