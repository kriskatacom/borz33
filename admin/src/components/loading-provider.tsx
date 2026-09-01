import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';

const SHOW_AFTER_MS = 120;
const MIN_VISIBLE_MS = 300;
const HIDE_AFTER_MS = 120;

type LoadingContextValue = {
  start: () => void;
  stop: () => void;
};

const LoadingContext = createContext<LoadingContextValue | null>(null);

export function LoadingProvider({ children }: { children: ReactNode }) {
  const [count, setCount] = useState(0);
  const [visible, setVisible] = useState(false);
  const shownAtRef = useRef<number | null>(null);
  const showTimerRef = useRef<number | null>(null);
  const hideTimerRef = useRef<number | null>(null);
  const start = useCallback(() => setCount((current) => current + 1), []);
  const stop = useCallback(() => setCount((current) => Math.max(0, current - 1)), []);
  const value = useMemo(() => ({ start, stop }), [start, stop]);

  useEffect(() => {
    if (count > 0) {
      if (hideTimerRef.current !== null) {
        window.clearTimeout(hideTimerRef.current);
        hideTimerRef.current = null;
      }

      if (!visible && showTimerRef.current === null) {
        showTimerRef.current = window.setTimeout(() => {
          showTimerRef.current = null;
          shownAtRef.current = Date.now();
          setVisible(true);
        }, SHOW_AFTER_MS);
      }

      return;
    }

    if (showTimerRef.current !== null) {
      window.clearTimeout(showTimerRef.current);
      showTimerRef.current = null;
    }

    if (!visible) {
      return;
    }

    const elapsed = Date.now() - (shownAtRef.current ?? Date.now());
    const wait = Math.max(HIDE_AFTER_MS, MIN_VISIBLE_MS - elapsed);

    hideTimerRef.current = window.setTimeout(() => {
      setVisible(false);
      shownAtRef.current = null;
      hideTimerRef.current = null;
    }, wait);

    return () => {
      if (hideTimerRef.current !== null) {
        window.clearTimeout(hideTimerRef.current);
        hideTimerRef.current = null;
      }
    };
  }, [count, visible]);

  useEffect(() => () => {
    if (showTimerRef.current !== null) {
      window.clearTimeout(showTimerRef.current);
    }
    if (hideTimerRef.current !== null) {
      window.clearTimeout(hideTimerRef.current);
    }
  }, []);

  return (
    <LoadingContext.Provider value={value}>
      {children}
      {visible ? (
        <div className="play-progress" role="progressbar" aria-busy="true" aria-label="Зареждане">
          <span className="play-progress-bar" />
          <span className="play-progress-bar is-late" />
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
