import { useEffect, useId, useState, type ReactNode } from 'react';
import { ChevronDown, type LucideIcon } from 'lucide-react';
import { HelpHint } from '@/components/ui/HelpHint';
import { cn } from '@/lib/utils';

const STORAGE_KEY = 'borz33.section-open';

function readOpen(key: string): boolean | null {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);

    if (!raw) {
      return null;
    }

    const stored = JSON.parse(raw) as Record<string, unknown>;
    const value = stored[key];

    return typeof value === 'boolean' ? value : null;
  } catch {
    return null;
  }
}

function writeOpen(key: string, open: boolean) {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    const stored = raw ? (JSON.parse(raw) as Record<string, unknown>) : {};

    if (typeof stored !== 'object' || stored === null || Array.isArray(stored)) {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({ [key]: open }));
      return;
    }

    stored[key] = open;
    localStorage.setItem(STORAGE_KEY, JSON.stringify(stored));
  } catch {
    // Private mode or full storage should not block toggling.
  }
}

type CollapsibleSectionProps = {
  title: ReactNode;
  children: ReactNode;
  icon?: LucideIcon;
  defaultOpen?: boolean;
  className?: string;
  heading?: 'h2' | 'h3';
  help?: string;
  actions?: ReactNode;
  persistKey?: string;
  forceOpen?: boolean;
};

export function CollapsibleSection({
  title,
  children,
  icon: Icon,
  defaultOpen = true,
  className,
  heading: Heading = 'h2',
  help,
  actions,
  persistKey,
  forceOpen = false,
}: CollapsibleSectionProps) {
  const storageKey = persistKey ?? (typeof title === 'string' ? `section:${title}` : undefined);
  const [open, setOpen] = useState(() => (storageKey ? (readOpen(storageKey) ?? defaultOpen) : defaultOpen));
  const panelId = useId();
  const helpLabel = typeof title === 'string' ? title : 'Секция';

  useEffect(() => {
    if (!forceOpen) {
      return;
    }

    setOpen(true);

    if (storageKey) {
      writeOpen(storageKey, true);
    }
  }, [forceOpen, storageKey]);

  function toggle() {
    setOpen((value) => {
      const next = !value;

      if (storageKey) {
        writeOpen(storageKey, next);
      }

      return next;
    });
  }

  return (
    <section
      className={cn(
        'collapsible-section h-fit w-full min-w-0 overflow-hidden rounded-[6px] border border-border bg-card',
        '[&_.collapsible-section]:bg-muted',
        '[&_.collapsible-section_.collapsible-section-header]:bg-[color-mix(in_srgb,var(--muted)_60%,var(--border))]',
        className
      )}
    >
      <Heading className="m-0">
        <div
          className={cn(
            'collapsible-section-header flex min-h-12 items-stretch border-b border-border bg-muted',
            !open && 'border-b-transparent'
          )}
        >
          <button
            type="button"
            className="flex min-w-0 flex-1 cursor-pointer items-center gap-3 px-3 py-2.5 text-left outline-none transition-colors hover:bg-muted/80 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:ring-inset"
            aria-expanded={open}
            aria-controls={panelId}
            onClick={toggle}
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
          {actions ? <div className="flex items-center gap-1 pr-2">{actions}</div> : null}
          {help ? (
            <div className="flex items-center pr-2">
              <HelpHint label={helpLabel}>{help}</HelpHint>
            </div>
          ) : null}
        </div>
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
