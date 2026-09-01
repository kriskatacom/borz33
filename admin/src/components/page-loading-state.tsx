import { LoaderCircle } from 'lucide-react';

export function PageLoadingState({ label = 'Зареждане на данните…' }: { label?: string }) {
  return (
    <div className="page page-loading-state" aria-busy="true" aria-live="polite">
      <div className="page-loading-card">
        <LoaderCircle className="size-5 animate-spin" aria-hidden />
        <span>{label}</span>
      </div>
    </div>
  );
}
