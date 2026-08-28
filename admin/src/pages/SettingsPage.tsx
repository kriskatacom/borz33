import { Monitor, Moon, Sun } from 'lucide-react';
import { routes } from '@/app/constants';
import { useTheme, type Theme } from '@/components/theme-provider';
import { PageHeader } from '@/components/page-header';
import { HelpHint } from '@/components/ui/HelpHint';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';

const options: Array<{ value: Theme; label: string; help: string; icon: typeof Sun }> = [
  { value: 'light', label: 'Светла', help: 'Бял фон с горско зелено, независимо от темата на устройството.', icon: Sun },
  { value: 'dark', label: 'Тъмна', help: 'Тъмен фон с по-мек контраст за вечерна работа.', icon: Moon },
  { value: 'system', label: 'Системна', help: 'Следва светлата или тъмната тема на устройството.', icon: Monitor },
];

export function SettingsPage() {
  const { theme, setTheme } = useTheme();

  return (
    <div className="page">
      <PageHeader
        title="Външен вид"
        help="Изберете светла, тъмна или системна тема. Изборът се запомня в този браузър."
        crumbs={[
          { label: 'Табло', to: routes.home },
          { label: 'Настройки' },
        ]}
      />

      <RadioGroup
        value={theme}
        onValueChange={(value) => setTheme(value as Theme)}
        className="grid max-w-xl gap-3"
        aria-label="Тема на приложението"
      >
        {options.map((option) => {
          const Icon = option.icon;

          return (
            <div
              key={option.value}
              className="flex items-center gap-3 rounded-[6px] border border-border bg-card p-4"
            >
              <Label
                htmlFor={`theme-${option.value}`}
                className="flex min-w-0 flex-1 cursor-pointer items-center gap-3 font-sans text-foreground"
              >
                <RadioGroupItem id={`theme-${option.value}`} value={option.value} />
                <Icon className="size-5 shrink-0 text-muted-foreground" aria-hidden />
                <span className="text-base font-bold">{option.label}</span>
              </Label>
              <HelpHint label={option.label}>{option.help}</HelpHint>
            </div>
          );
        })}
      </RadioGroup>
    </div>
  );
}
