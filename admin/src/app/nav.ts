import { routes } from '@/app/constants';

export type NavItem = {
  to: string;
  label: string;
  hint: string;
  mobile?: boolean;
};

export type NavSection = {
  label: string;
  items: NavItem[];
};

export const navItems: NavItem[] = [
  { to: routes.home, label: 'Табло', hint: 'Преглед и бързи връзки', mobile: true },
  { to: routes.orders, label: 'Поръчки', hint: 'Нови заявки, статуси и плащания', mobile: true },
  { to: routes.products, label: 'Продукти', hint: 'Каталог, цени и наличности', mobile: true },
  { to: routes.categories, label: 'Категории', hint: 'Дърво на каталога и изображения' },
  { to: routes.media, label: 'Медия', hint: 'Файлове и изображения' },
  { to: routes.users, label: 'Потребители', hint: 'Екип и клиентски профили' },
  { to: routes.shipments, label: 'Доставки', hint: 'Econt, товарителници и куриер' },
  { to: routes.pages, label: 'Страници', hint: 'CMS страници и персонални полета' },
  { to: routes.banners, label: 'Банери', hint: 'Текст, изображение и бутони за сайта' },
  { to: routes.campaigns, label: 'Кампании', hint: 'Промоции и купони' },
  { to: routes.messages, label: 'Съобщения', hint: 'Писма и известия' },
  { to: routes.reports, label: 'Отчети', hint: 'Продажби и счетоводни данни' },
  { to: routes.settings, label: 'Настройки', hint: 'Магазин, екип и достъп' },
];

export const navSections: NavSection[] = [
  { label: 'Начало', items: navItems.filter((item) => item.to === routes.home) },
  { label: 'Продажби', items: navItems.filter((item) => new Set<string>([routes.orders, routes.shipments]).has(item.to)) },
  { label: 'Каталог', items: navItems.filter((item) => new Set<string>([routes.products, routes.categories, routes.media]).has(item.to)) },
  { label: 'Съдържание', items: navItems.filter((item) => new Set<string>([routes.pages, routes.banners, routes.campaigns]).has(item.to)) },
  { label: 'Комуникация', items: navItems.filter((item) => item.to === routes.messages) },
  { label: 'Анализи и настройки', items: navItems.filter((item) => new Set<string>([routes.reports, routes.users, routes.settings]).has(item.to)) },
];

export function navItemByPath(path: string): NavItem | undefined {
  if (path.startsWith(`${routes.users}/`) || path === routes.users) {
    return navItems.find((item) => item.to === routes.users);
  }

  if (path.startsWith(`${routes.products}/`) || path === routes.products) {
    return navItems.find((item) => item.to === routes.products);
  }

  if (path.startsWith(`${routes.categories}/`) || path === routes.categories) {
    return navItems.find((item) => item.to === routes.categories);
  }

  if (path === routes.media) {
    return navItems.find((item) => item.to === routes.media);
  }

  if (path.startsWith(`${routes.pages}/`) || path === routes.pages || path === routes.content) {
    return navItems.find((item) => item.to === routes.pages);
  }

  if (path.startsWith(`${routes.banners}/`) || path === routes.banners) {
    return navItems.find((item) => item.to === routes.banners);
  }

  return navItems.find((item) => item.to === path);
}
