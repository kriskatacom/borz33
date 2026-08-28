import { Link } from 'react-router-dom';
import { ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';

export type BreadcrumbItem = {
  label: string;
  to?: string;
};

export function Breadcrumbs({ items }: { items: BreadcrumbItem[] }) {
  if (items.length === 0) {
    return null;
  }

  return (
    <nav aria-label="Път" className="min-w-0">
      <ol className="m-0 flex min-w-0 list-none flex-wrap items-center gap-1 p-0 text-sm text-muted-foreground">
        {items.map((item, index) => {
          const last = index === items.length - 1;

          return (
            <li key={`${item.label}-${index}`} className="flex items-center gap-1">
              {index > 0 ? <ChevronRight className="size-3.5 shrink-0 opacity-50" aria-hidden /> : null}
              {last || !item.to ? (
                <span className={cn(last && 'text-foreground')}>{item.label}</span>
              ) : (
                <Link to={item.to} className="font-normal hover:text-foreground">
                  {item.label}
                </Link>
              )}
            </li>
          );
        })}
      </ol>
    </nav>
  );
}
