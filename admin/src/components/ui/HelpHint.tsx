import type { MouseEvent, PointerEvent, ReactNode } from 'react';
import { CircleHelp } from 'lucide-react';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type HelpHintProps = {
  label: string;
  children: ReactNode;
  className?: string;
};

function stopLabelActivation(event: MouseEvent | PointerEvent) {
  event.stopPropagation();
}

export function HelpHint({ label, children, className }: HelpHintProps) {
  return (
    <Popover>
      <PopoverTrigger asChild>
        <button
          type="button"
          className={cn(
            'inline-flex size-6 shrink-0 items-center justify-center rounded-[6px] text-muted-foreground hover:bg-accent hover:text-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
            className
          )}
          aria-label={`Помощ: ${label}`}
          onClick={stopLabelActivation}
          onPointerDown={stopLabelActivation}
        >
          <CircleHelp className="size-4" aria-hidden />
        </button>
      </PopoverTrigger>
      <PopoverContent>{children}</PopoverContent>
    </Popover>
  );
}

export function LabelWithHelp({
  htmlFor,
  label,
  help,
  className,
}: {
  htmlFor?: string;
  label: string;
  help: string;
  className?: string;
}) {
  return (
    <div className={cn('flex items-center gap-1', className)}>
      {htmlFor ? <Label htmlFor={htmlFor}>{label}</Label> : <span className="text-base font-medium">{label}</span>}
      <HelpHint label={label}>{help}</HelpHint>
    </div>
  );
}
