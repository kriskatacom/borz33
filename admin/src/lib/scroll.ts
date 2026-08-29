export function scrollPageToTop(options?: { keepFieldFocus?: boolean }) {
  const active = document.activeElement;
  const keepFocus =
    options?.keepFieldFocus !== false &&
    active instanceof HTMLElement &&
    active.matches('input, textarea, select, [contenteditable="true"]');

  if (!keepFocus && active instanceof HTMLElement) {
    active.blur();
  }

  const jump = () => {
    window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
    document.documentElement.scrollTop = 0;
    document.body.scrollTop = 0;
  };

  const settle = () => {
    if (!keepFocus) {
      const heading = document.querySelector('.admin-main h1');

      if (heading instanceof HTMLElement) {
        heading.focus({ preventScroll: true });
      }
    }

    jump();
  };

  jump();
  requestAnimationFrame(() => {
    settle();
    requestAnimationFrame(settle);
  });
}
