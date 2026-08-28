const button = document.querySelector('[data-menu-button]');
const menu = document.querySelector('[data-mobile-menu]');

if (button && menu) {
  button.addEventListener('click', () => {
    const isOpen = button.getAttribute('aria-expanded') === 'true';
    button.setAttribute('aria-expanded', String(!isOpen));
    button.setAttribute('aria-label', isOpen ? 'Отвори менюто' : 'Затвори менюто');
    menu.classList.toggle('hidden', isOpen);
  });
}
