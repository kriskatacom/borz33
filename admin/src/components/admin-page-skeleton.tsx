type AdminPageSkeletonProps = {
  variant?: 'form' | 'settings' | 'reports';
  sections?: number;
};

function Line({ className = '' }: { className?: string }) {
  return <span className={`admin-page-skeleton-line ${className}`} aria-hidden />;
}

/** A structural placeholder for pages whose content is loaded after the header. */
export function AdminPageSkeleton({ variant = 'form', sections = 3 }: AdminPageSkeletonProps) {
  if (variant === 'reports') {
    return <div className="admin-page-skeleton-report-list" aria-label="Зареждане на отчетите" aria-busy="true">
      {Array.from({ length: 2 }, (_, index) => <article className="admin-page-skeleton-report" key={index}><div><Line className="w-24" /><Line className="mt-3 w-48" /><Line className="mt-3 w-32" /></div><Line className="h-8 w-28" /><div className="admin-page-skeleton-metrics">{Array.from({ length: 4 }, (_, metric) => <div key={metric}><Line className="w-24" /><Line className="mt-3 w-16" /></div>)}</div></article>)}
    </div>;
  }

  return <div className={`admin-page-skeleton admin-page-skeleton--${variant}`} aria-label="Зареждане на данните" aria-busy="true">
    {Array.from({ length: sections }, (_, index) => <section className="admin-page-skeleton-section" key={index}>
      <header><Line className="size-5 rounded-full" /><Line className="w-36" /></header>
      <div className="admin-page-skeleton-fields">
        <div><Line className="w-20" /><Line className="mt-2 h-11 w-full" /></div>
        <div><Line className="w-28" /><Line className="mt-2 h-11 w-full" /></div>
        {variant === 'settings' ? <div><Line className="w-40" /><Line className="mt-2 h-5 w-3/4" /></div> : null}
      </div>
    </section>)}
  </div>;
}

export function MediaGridSkeleton() {
  return <div className="media-grid-skeleton" aria-label="Зареждане на файловете" aria-busy="true">
    {Array.from({ length: 10 }, (_, index) => <article key={index}><div className="product-image-skeleton aspect-square" /><div className="grid gap-2 p-2"><Line className="w-3/4" /><Line className="w-1/2" /></div></article>)}
  </div>;
}

export function InvoiceDetailsSkeleton() {
  const rows = Array.from({ length: 4 }, (_, index) => index);

  return <div className="page" aria-label="Зареждане на документа" aria-busy="true">
    <div className="invoice-details-skeleton-header"><Line className="h-8 w-56" /><Line className="mt-3 w-72" /></div>
    <div className="invoice-details-skeleton">
      <section className="invoice-details-skeleton-document">
        <div className="grid gap-4 border-b border-border p-4 md:grid-cols-2">{Array.from({ length: 2 }, (_, index) => <div key={index}><Line className="w-20" /><Line className="mt-3 w-40" /><Line className="mt-2 w-56" /><Line className="mt-2 w-44" /></div>)}</div>
        <div className="invoice-details-skeleton-table"><header>{Array.from({ length: 5 }, (_, index) => <Line key={index} className={index === 0 ? 'w-24' : 'w-14'} />)}</header>{rows.map((row) => <div key={row}>{Array.from({ length: 5 }, (_, index) => <Line key={index} className={index === 0 ? 'w-36' : 'w-16'} />)}</div>)}</div>
        <div className="ml-auto grid max-w-sm gap-3 p-4"><Line className="w-full" /><Line className="w-full" /><Line className="h-6 w-full" /></div>
      </section>
      <aside className="grid content-start gap-4"><section className="border border-border bg-card p-4"><Line className="w-32" /><Line className="mt-4 w-20" /><Line className="mt-3 w-full" /><Line className="mt-2 w-3/4" /></section><section className="border border-border bg-card p-4"><Line className="w-40" /><Line className="mt-4 w-full" /><Line className="mt-2 w-full" /><Line className="mt-5 h-10 w-full" /></section></aside>
    </div>
  </div>;
}
