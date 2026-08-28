import { useId, useState, type ReactNode } from 'react';
import { ChevronDown, type LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

type CollapsibleSectionProps = {
  title: ReactNode;
  children: ReactNode;
  icon?: LucideIcon;
  defaultOpen?: boolean;
  className?: string;
  heading?: 'h2' | 'h3';
};

export function CollapsibleSection({
  title,
  children,
  icon: Icon,
  defaultOpen = true,
  className,
  heading: Heading = 'h2',
}: CollapsibleSectionProps) {
  const [open, setOpen] = useState(defaultOpen);
  const panelId = useId();

  return (
    <section
      className={cn(
        'h-fit w-full min-w-0 overflow-hidden rounded-[6px] border border-border bg-card',
        className
      )}
    >
      <Heading className="m-0">
        <button
          type="button"
          className={cn(
            'flex min-h-12 w-full cursor-pointer items-center gap-3 border-b border-border bg-muted px-3 py-2.5 text-left outline-none transition-colors hover:bg-muted/80 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:ring-inset',
            !open && 'border-b-transparent'
          )}
          aria-expanded={open}
          aria-controls={panelId}
          onClick={() => setOpen((value) => !value)}
        >
          {Icon ? (
            <span className="pointer-events-none flex size-8 shrink-0 items-center justify-center rounded-[6px] bg-primary/12 text-primary">
              <Icon className="size-4" aria-hidden />
            </span>
          ) : null}
          <span
            className={cn(
              'pointer-events-none min-w-0 flex-1 font-sans text-sm font-extrabold tracking-wide text-foreground',
              typeof title === 'string' && 'uppercase'
            )}
          >
            {title}
          </span>
          <span className="pointer-events-none flex size-8 shrink-0 items-center justify-center rounded-[6px] border border-border bg-card text-muted-foreground">
            <ChevronDown
              className={cn('size-4 transition-transform duration-200', open && 'rotate-180')}
              aria-hidden
            />
          </span>
        </button>
      </Heading>
      <div
        id={panelId}
        hidden={!open}
        inert={open ? undefined : true}
        className={cn('min-w-0 px-3 py-3', !open && 'pointer-events-none')}
      >
        {children}
      </div>
    </section>
  );
}
