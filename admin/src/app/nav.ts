import { routes } from '@/app/constants';

export type NavItem = {
  to: string;
  label: string;
  hint: string;
  mobile?: boolean;
};

export const navItems: NavItem[] = [
  { to: routes.home, label: 'Табло', hint: 'Преглед и бързи връзки', mobile: true },
  { to: routes.orders, label: 'Поръчки', hint: 'Нови заявки, статуси и плащания', mobile: true },
  { to: routes.products, label: 'Продукти', hint: 'Каталог, цени и наличности', mobile: true },
  { to: routes.media, label: 'Медия', hint: 'Файлове и изображения' },
  { to: routes.users, label: 'Потребители', hint: 'Екип и клиентски профили' },
  { to: routes.shipments, label: 'Доставки', hint: 'Econt, товарителници и куриер' },
  { to: routes.content, label: 'Съдържание', hint: 'Страници, менюта и банери' },
  { to: routes.campaigns, label: 'Кампании', hint: 'Промоции и купони' },
  { to: routes.messages, label: 'Съобщения', hint: 'Писма и известия' },
  { to: routes.reports, label: 'Отчети', hint: 'Продажби и счетоводни данни' },
  { to: routes.settings, label: 'Настройки', hint: 'Магазин, екип и достъп' },
];

export function navItemByPath(path: string): NavItem | undefined {
  if (path.startsWith(`${routes.users}/`) || path === routes.users) {
    return navItems.find((item) => item.to === routes.users);
  }

  if (path.startsWith(`${routes.products}/`) || path === routes.products) {
    return navItems.find((item) => item.to === routes.products);
  }

  if (path === routes.media) {
    return navItems.find((item) => item.to === routes.media);
  }

  return navItems.find((item) => item.to === path);
}
