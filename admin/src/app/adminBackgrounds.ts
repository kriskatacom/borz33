export type AdminBackgroundOption = { value: string; label: string; help: string; css: string };

export const adminBackgroundOptions: AdminBackgroundOption[] = [
  { value: '', label: 'Без фон', help: 'Използва стандартния фон на избраната тема.', css: 'none' },
];

export const adminColorOptions: AdminBackgroundOption[] = [
  { value: 'solid-graphite', label: 'Графит', help: 'Спокоен плътен тъмен фон с висок контраст.', css: '#18221f' },
  { value: 'solid-forest', label: 'Горско зелено', help: 'Плътен цвят в основната визуална идентичност.', css: '#173f32' },
  { value: 'solid-slate', label: 'Синьо-сив', help: 'Неутрален плътен цвят за дълги работни сесии.', css: '#334155' },
  { value: 'gradient-forest', label: 'Горски градиент', help: 'Мек преход между дълбоко зелено и синьо-зелено.', css: 'linear-gradient(135deg, #173f32 0%, #2d6a4f 52%, #0f2f3d 100%)' },
  { value: 'gradient-ocean', label: 'Океански градиент', help: 'Студен градиент с чист и модерен вид.', css: 'linear-gradient(135deg, #0f172a 0%, #155e75 52%, #0e7490 100%)' },
  { value: 'gradient-sunset', label: 'Топъл градиент', help: 'Тъмен графитен фон с дискретен топъл акцент.', css: 'linear-gradient(135deg, #171717 0%, #3f2b3d 55%, #7c3f35 100%)' },
  { value: 'solid-midnight', label: 'Среднощно синьо', help: 'Дълбок син цвят с мек, спокоен контраст.', css: '#172554' },
  { value: 'solid-plum', label: 'Тъмна слива', help: 'Елегантен плътен цвят с топъл нюанс.', css: '#3b1d3f' },
  { value: 'solid-teal', label: 'Тъмно тюркоазено', help: 'Наситен синьо-зелен цвят за модерен панел.', css: '#134e4a' },
  { value: 'gradient-aurora', label: 'Северно сияние', help: 'Зеленo-син градиент с по-жив акцент.', css: 'linear-gradient(135deg, #052e16 0%, #115e59 50%, #164e63 100%)' },
  { value: 'gradient-violet', label: 'Виолетов градиент', help: 'Студен тъмен градиент с лилав характер.', css: 'linear-gradient(135deg, #1e1b4b 0%, #4c1d95 52%, #312e81 100%)' },
  { value: 'gradient-copper', label: 'Меден градиент', help: 'Графитен преход с приглушен меден акцент.', css: 'linear-gradient(135deg, #1c1917 0%, #44403c 52%, #78350f 100%)' },
  { value: 'gradient-mist', label: 'Утринна мъгла', help: 'Светъл, мек градиент с неутрален и спокоен вид.', css: 'linear-gradient(135deg, #e2e8f0 0%, #f8fafc 52%, #dbeafe 100%)' },
  { value: 'gradient-sage', label: 'Светла салвия', help: 'Свеж преход между светло зелено и кремаво.', css: 'linear-gradient(135deg, #d9f99d 0%, #ecfccb 48%, #fef3c7 100%)' },
  { value: 'gradient-sky', label: 'Небесен', help: 'Чист светъл градиент със син и лавандулов акцент.', css: 'linear-gradient(135deg, #bae6fd 0%, #e0f2fe 48%, #ede9fe 100%)' },
  { value: 'gradient-peach', label: 'Прасковен', help: 'Топъл и светъл градиент с деликатен розов нюанс.', css: 'linear-gradient(135deg, #fed7aa 0%, #ffedd5 48%, #fce7f3 100%)' },
  { value: 'gradient-aqua', label: 'Светъл аквамарин', help: 'Светъл свеж фон между тюркоазено и синьо.', css: 'linear-gradient(135deg, #99f6e4 0%, #ccfbf1 48%, #cffafe 100%)' },
];

export function adminBackgroundCss(value: string | null): string {
  const option = [...adminBackgroundOptions, ...adminColorOptions].find((item) => item.value === value);
  if (option) return option.css;
  if (value?.startsWith('admin-backgrounds/')) return `url(${JSON.stringify('/' + value)})`;

  return 'none';
}
