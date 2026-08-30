import type { ReactNode } from 'react';
import { HelpHint } from '@/components/ui/HelpHint';
import { Breadcrumbs, type BreadcrumbItem } from '@/components/ui/breadcrumb';

type PageHeaderProps = {
  title: string;
  help?: ReactNode;
  crumbs?: BreadcrumbItem[];
  actions?: ReactNode;
};

export function PageHeader({ title, help, crumbs, actions }: PageHeaderProps) {
  return (
    <header className="mb-2 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
      <div className="page-header-content grid min-w-0 flex-1 gap-1 pl-2 sm:pl-3 lg:pl-4">
        <h1 tabIndex={-1} className="m-0 flex min-w-0 items-center gap-1.5 text-xl! font-normal outline-none">
          <span className="min-w-0 [overflow-wrap:anywhere]">{title}</span>
          {help ? <HelpHint label={title}>{help}</HelpHint> : null}
        </h1>
        {crumbs && crumbs.length > 0 ? <Breadcrumbs items={crumbs} /> : null}
      </div>
      {actions ? (
        <div className="flex w-full shrink-0 flex-wrap items-center gap-2 sm:w-auto sm:justify-end [&_[data-slot=button]]:w-full sm:[&_[data-slot=button]]:w-auto">
          {actions}
        </div>
      ) : null}
    </header>
  );
}
