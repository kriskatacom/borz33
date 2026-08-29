export function registerTooltip(Alpine) {
  document.addEventListener('alpine:init', () => {
    Alpine.data('storeTooltip', (label = '') => ({
      open: false,
      timer: null,
      left: 0,
      top: 0,
      label,

      get style() {
        return `left:${this.left}px;top:${this.top}px`;
      },

      prefersHover() {
        return window.matchMedia('(hover: hover) and (pointer: fine)').matches;
      },

      show(fromFocus = false) {
        if (!fromFocus && !this.prefersHover()) {
          return;
        }

        clearTimeout(this.timer);
        this.timer = setTimeout(() => {
          const trigger = this.$el.querySelector('.store-icon-btn') ?? this.$el;
          const rect = trigger.getBoundingClientRect();
          this.left = rect.left + rect.width / 2;
          this.top = rect.bottom + 8;
          this.open = true;
        }, 180);
      },

      hide() {
        clearTimeout(this.timer);
        this.open = false;
      },
    }));
  });
}
