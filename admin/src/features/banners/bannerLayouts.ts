export const BANNER_LAYOUTS = [
  {
    value: 'split',
    label: 'Разделен',
    help: 'Изображение вляво, текст и бутони вдясно. Това е дизайнът на „Пролетна промоция“.',
  },
  {
    value: 'overlay',
    label: 'Насложен',
    help: 'Текстът и бутоните са върху изображението, като герой секция.',
  },
  {
    value: 'stack',
    label: 'Подреден',
    help: 'Изображението е отгоре, текстът и бутоните са под него.',
  },
] as const;

export type BannerLayout = (typeof BANNER_LAYOUTS)[number]['value'];

export function isBannerLayout(value: string): value is BannerLayout {
  return BANNER_LAYOUTS.some((layout) => layout.value === value);
}

export function bannerLayoutLabel(value: string): string {
  return BANNER_LAYOUTS.find((layout) => layout.value === value)?.label ?? 'Разделен';
}
