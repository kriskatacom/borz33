import { useEffect, useRef, useState, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { cn } from '@/lib/utils';

type TooltipProps = {
  content: string;
  children: ReactNode;
  className?: string;
};

export function Tooltip({ content, children, className }: TooltipProps) {
  const triggerRef = useRef<HTMLSpanElement>(null);
  const [open, setOpen] = useState(false);
  const [coords, setCoords] = useState({ top: 0, left: 0, below: false });
  const delayRef = useRef<number>(0);

  function place() {
    const node = triggerRef.current;

    if (!node) {
      return;
    }

    const box = node.getBoundingClientRect();
    const below = box.top < 44;
    setCoords({
      top: below ? box.bottom + 8 : box.top - 8,
      left: box.left + box.width / 2,
      below,
    });
  }

  function show() {
    window.clearTimeout(delayRef.current);
    delayRef.current = window.setTimeout(() => {
      place();
      setOpen(true);
    }, 280);
  }

  function hide() {
    window.clearTimeout(delayRef.current);
    setOpen(false);
  }

  useEffect(() => {
    return () => window.clearTimeout(delayRef.current);
  }, []);

  useEffect(() => {
    if (!open) {
      return;
    }

    function onScroll() {
      place();
    }

    window.addEventListener('scroll', onScroll, true);
    window.addEventListener('resize', onScroll);

    return () => {
      window.removeEventListener('scroll', onScroll, true);
      window.removeEventListener('resize', onScroll);
    };
  }, [open]);

  const text = content.trim();

  if (text === '') {
    return children;
  }

  return (
    <>
      <span
        ref={triggerRef}
        className={cn('inline-flex max-w-full', className)}
        onMouseEnter={show}
        onMouseLeave={hide}
        onFocusCapture={show}
        onBlurCapture={hide}
        onPointerDown={hide}
      >
        {children}
      </span>
      {open
        ? createPortal(
            <span
              role="tooltip"
              className={cn(
                'pointer-events-none fixed z-[80] max-w-64 -translate-x-1/2 rounded-[6px] border border-border bg-popover px-2 py-1 text-center text-sm leading-snug text-popover-foreground shadow-md',
                coords.below ? 'translate-y-0' : '-translate-y-full'
              )}
              style={{ top: coords.top, left: coords.left }}
            >
              {text}
            </span>,
            document.body
          )
        : null}
    </>
  );
}
