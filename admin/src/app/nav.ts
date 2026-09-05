import type { LucideIcon } from 'lucide-react';
import { BarChart3, Bell, Box, FileText, FolderTree, Image, LayoutDashboard, Landmark, Megaphone, MessageCircle, Palette, ReceiptText, Settings, ShoppingBag, Users } from 'lucide-react';
import { routes } from '@/app/constants';

export type NavItem = {
  to: string;
  label: string;
  hint: string;
  icon: LucideIcon;
  mobile?: boolean;
};

export type NavSection = {
  label: string;
  items: NavItem[];
};

export const navItems: NavItem[] = [
  { to: routes.home, label: 'Табло', hint: 'Преглед и бързи връзки', icon: LayoutDashboard, mobile: true },
  { to: routes.notifications, label: 'Известия', hint: 'Важни събития в магазина', icon: Bell, mobile: true },
  { to: routes.orders, label: 'Поръчки', hint: 'Нови заявки, статуси и плащания', icon: ShoppingBag, mobile: true },
  { to: routes.invoices, label: 'Фактури', hint: 'Издадени фактури и експорт', icon: ReceiptText },
  { to: routes.creditNotes, label: 'Кредитни известия', hint: 'Кредитни известия и експорт', icon: FileText },
  { to: routes.products, label: 'Продукти', hint: 'Каталог, цени и наличности', icon: Box, mobile: true },
  { to: routes.categories, label: 'Категории', hint: 'Дърво на каталога и изображения', icon: FolderTree },
  { to: routes.media, label: 'Медия', hint: 'Файлове и изображения', icon: Image },
  { to: routes.users, label: 'Потребители', hint: 'Екип и клиентски профили', icon: Users },
  { to: routes.pages, label: 'Страници', hint: 'CMS страници и персонални полета', icon: FileText },
  { to: routes.banners, label: 'Банери', hint: 'Текст, изображение и бутони за сайта', icon: Megaphone },
  { to: routes.messages, label: 'Съобщения', hint: 'Писма и известия', icon: MessageCircle },
  { to: routes.reports, label: 'Отчети', hint: 'Продажби и счетоводни данни', icon: BarChart3 },
  { to: routes.accounting, label: 'Счетоводство', hint: 'Плащания, справки и месечно приключване', icon: Landmark },
  { to: routes.settings, label: 'Настройки', hint: 'Магазин, екип и достъп', icon: Settings },
  { to: routes.customization, label: 'Персонализиране', hint: 'Фон на административния панел', icon: Palette },
];

export const navSections: NavSection[] = [
  { label: 'Начало', items: navItems.filter((item) => item.to === routes.home || item.to === routes.notifications) },
  { label: 'Продажби', items: navItems.filter((item) => new Set<string>([routes.orders, routes.invoices, routes.creditNotes]).has(item.to)) },
  { label: 'Каталог', items: navItems.filter((item) => new Set<string>([routes.products, routes.categories, routes.media]).has(item.to)) },
  { label: 'Съдържание', items: navItems.filter((item) => new Set<string>([routes.pages, routes.banners]).has(item.to)) },
  { label: 'Комуникация', items: navItems.filter((item) => item.to === routes.messages) },
  { label: 'Анализи', items: navItems.filter((item) => new Set<string>([routes.reports, routes.accounting]).has(item.to)) },
  { label: 'Управление', items: navItems.filter((item) => item.to === routes.users) },
  { label: 'Настройки', items: navItems.filter((item) => new Set<string>([routes.settings, routes.customization]).has(item.to)) },
];

export function navItemByPath(path: string): NavItem | undefined {
  if (path.startsWith(`${routes.users}/`) || path === routes.users) {
    return navItems.find((item) => item.to === routes.users);
  }

  if (path.startsWith(`${routes.products}/`) || path === routes.products) {
    return navItems.find((item) => item.to === routes.products);
  }
  if (path.startsWith(`${routes.invoices}/`) || path === routes.invoices) return navItems.find((item) => item.to === routes.invoices);
  if (path.startsWith(`${routes.creditNotes}/`) || path === routes.creditNotes) return navItems.find((item) => item.to === routes.creditNotes);
  if (path.startsWith(`${routes.accounting}/`) || path === routes.accounting) return navItems.find((item) => item.to === routes.accounting);
  if (path.startsWith(`${routes.notifications}/`) || path === routes.notifications) return navItems.find((item) => item.to === routes.notifications);

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
