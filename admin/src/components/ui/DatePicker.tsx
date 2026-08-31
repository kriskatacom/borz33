import { useMemo, useState } from 'react';
import { format } from 'date-fns';
import { bg } from 'date-fns/locale';
import { CalendarDays } from 'lucide-react';
import { DayPicker, type Matcher } from 'react-day-picker';
import { Button } from '@/components/ui/Button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';

type DatePickerProps = {
  id: string;
  value: string;
  onChange: (value: string) => void;
  label: string;
  min?: string;
  max?: string;
};

function parseDate(value: string): Date | undefined {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return undefined;
  const [year, month, day] = value.split('-').map(Number);
  const date = new Date(year, month - 1, day);
  return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day ? date : undefined;
}

function calendarClassNames() {
  return {
    root: 'relative text-sm',
    months: 'flex flex-col',
    month: 'space-y-3',
    month_caption: 'flex h-9 items-center justify-center px-9',
    caption_label: 'font-semibold capitalize',
    nav: 'flex items-center justify-between',
    button_previous: 'absolute left-2 top-2 inline-flex size-8 items-center justify-center rounded-[6px] hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-40',
    button_next: 'absolute right-2 top-2 inline-flex size-8 items-center justify-center rounded-[6px] hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-40',
    month_grid: 'w-full border-collapse',
    weekdays: 'flex',
    weekday: 'w-9 pb-1 text-center text-xs font-medium text-muted-foreground',
    week: 'mt-1 flex w-full',
    day: 'size-9 p-0 text-center',
    day_button: 'inline-flex size-9 items-center justify-center rounded-[6px] font-normal transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
    selected: '[&>button]:bg-primary [&>button]:text-primary-foreground [&>button]:hover:bg-primary/90',
    today: '[&>button]:font-bold [&>button]:text-primary',
    outside: 'text-muted-foreground opacity-45',
    disabled: 'text-muted-foreground opacity-35 [&>button]:pointer-events-none',
  };
}

/** A localized calendar control which stores date values as YYYY-MM-DD. */
export function DatePicker({ id, value, onChange, label, min, max }: DatePickerProps) {
  const [open, setOpen] = useState(false);
  const selected = useMemo(() => parseDate(value), [value]);
  const minDate = useMemo(() => parseDate(min ?? ''), [min]);
  const maxDate = useMemo(() => parseDate(max ?? ''), [max]);
  const disabled: Matcher[] = [];
  if (minDate) disabled.push({ before: minDate });
  if (maxDate) disabled.push({ after: maxDate });

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button id={id} type="button" variant="outline" aria-label={label} aria-haspopup="dialog" className="min-h-12 w-full justify-start font-normal">
          <CalendarDays />
          {selected ? format(selected, 'd MMMM yyyy', { locale: bg }) : 'Изберете дата'}
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-auto p-2" align="start">
        <DayPicker
          mode="single"
          locale={bg}
          selected={selected}
          defaultMonth={selected ?? maxDate ?? new Date()}
          startMonth={minDate}
          endMonth={maxDate}
          disabled={disabled.length > 0 ? disabled : undefined}
          onSelect={(date) => {
            if (!date) return;
            onChange(format(date, 'yyyy-MM-dd'));
            setOpen(false);
          }}
          classNames={calendarClassNames()}
        />
        {selected ? <Button type="button" variant="ghost" size="sm" className="mt-1 w-full" onClick={() => { onChange(''); setOpen(false); }}>Изчисти датата</Button> : null}
      </PopoverContent>
    </Popover>
  );
}
