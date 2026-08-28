import { Monitor, Moon, Sun } from 'lucide-react';
import { useTheme, type Theme } from '@/components/theme-provider';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';

const options: Array<{ value: Theme; label: string; hint: string; icon: typeof Sun }> = [
  { value: 'light', label: 'Светла', hint: 'Крем и горско зелено, независимо от системата.', icon: Sun },
  { value: 'dark', label: 'Тъмна', hint: 'Тъмен фон с по-мек контраст за вечерна работа.', icon: Moon },
  { value: 'system', label: 'Системна', hint: 'Следва светлата или тъмната тема на устройството.', icon: Monitor },
];

export function SettingsPage() {
  const { theme, setTheme } = useTheme();

  return (
    <div className="page">
      <header className="page-head">
        <p className="eyebrow">Настройки</p>
        <h1>Външен вид</h1>
        <p className="muted">Изберете светла, тъмна или системна тема. Изборът се запомня в този браузър.</p>
      </header>

      <RadioGroup
        value={theme}
        onValueChange={(value) => setTheme(value as Theme)}
        className="grid max-w-xl gap-3"
        aria-label="Тема на приложението"
      >
        {options.map((option) => {
          const Icon = option.icon;

          return (
            <Label
              key={option.value}
              htmlFor={`theme-${option.value}`}
              className="flex cursor-pointer items-start gap-3 rounded-[22px] border border-border bg-card p-4 font-sans text-foreground"
            >
              <RadioGroupItem id={`theme-${option.value}`} value={option.value} className="mt-1" />
              <Icon className="mt-0.5 size-5 shrink-0 text-muted-foreground" aria-hidden />
              <span className="grid gap-1">
                <span className="text-base font-bold">{option.label}</span>
                <span className="text-sm font-normal text-muted-foreground">{option.hint}</span>
              </span>
            </Label>
          );
        })}
      </RadioGroup>
    </div>
  );
}
