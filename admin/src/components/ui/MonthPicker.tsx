import { useMemo, useState } from 'react';
import { endOfMonth, format } from 'date-fns';
import { bg } from 'date-fns/locale';
import { CalendarDays } from 'lucide-react';
import { DayPicker } from 'react-day-picker';
import { Button } from '@/components/ui/Button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

type MonthPickerProps = {
  id?: string;
  value: string;
  onChange: (value: string) => void;
  max?: string;
  className?: string;
};

function parsePeriod(value: string): Date | undefined {
  if (!/^\d{4}-\d{2}$/.test(value)) return undefined;
  const [year, month] = value.split('-').map(Number);
  return new Date(year, month - 1, 1);
}

function toPeriod(date: Date): string {
  return format(date, 'yyyy-MM');
}

/** A Bulgarian, keyboard-friendly calendar control that stores a YYYY-MM value. */
export function MonthPicker({ id, value, onChange, max, className }: MonthPickerProps) {
  const [open, setOpen] = useState(false);
  const selected = useMemo(() => parsePeriod(value), [value]);
  const maxDate = useMemo(() => parsePeriod(max ?? ''), [max]);
  const label = selected ? format(selected, 'LLLL yyyy', { locale: bg }) : 'Изберете месец';

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          id={id}
          type="button"
          variant="outline"
          aria-label="Изберете месец за отчета"
          aria-haspopup="dialog"
          className={cn('min-h-12 w-full justify-start font-normal capitalize', className)}
        >
          <CalendarDays />
          {label}
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-auto p-2" align="start">
        <DayPicker
          mode="single"
          locale={bg}
          selected={selected}
          defaultMonth={selected ?? maxDate ?? new Date()}
          endMonth={maxDate}
          disabled={maxDate ? { after: endOfMonth(maxDate) } : undefined}
          onSelect={(date) => {
            if (!date) return;
            onChange(toPeriod(date));
            setOpen(false);
          }}
          classNames={{
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
          }}
        />
      </PopoverContent>
    </Popover>
  );
}
