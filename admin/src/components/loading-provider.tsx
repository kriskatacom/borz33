import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { Loader2 } from 'lucide-react';

type LoadingContextValue = {
  start: () => void;
  stop: () => void;
};

const LoadingContext = createContext<LoadingContextValue | null>(null);

export function LoadingProvider({ children }: { children: ReactNode }) {
  const [count, setCount] = useState(0);
  const start = useCallback(() => setCount((current) => current + 1), []);
  const stop = useCallback(() => setCount((current) => Math.max(0, current - 1)), []);
  const value = useMemo(() => ({ start, stop }), [start, stop]);

  return (
    <LoadingContext.Provider value={value}>
      {children}
      {count > 0 ? (
        <div className="fixed inset-0 z-[80] grid place-items-center bg-background/70" role="status" aria-live="polite">
          <div className="flex items-center gap-3 rounded-[6px] border border-border bg-card px-4 py-3 font-sans text-sm text-foreground shadow-md">
            <Loader2 className="size-5 animate-spin text-primary" aria-hidden />
            Зареждане…
          </div>
        </div>
      ) : null}
    </LoadingContext.Provider>
  );
}

export function useGlobalLoading(active: boolean) {
  const context = useContext(LoadingContext);

  if (!context) {
    throw new Error('useGlobalLoading must be used within a LoadingProvider');
  }

  const { start, stop } = context;

  useEffect(() => {
    if (!active) {
      return;
    }

    start();
    return () => stop();
  }, [active, start, stop]);
}
