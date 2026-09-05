import { useEffect, useState, type ReactNode } from 'react';

type FilterDialogProps = {
  open: boolean;
  children: ReactNode;
};

export function FilterDialog({ open, children }: FilterDialogProps) {
  const [rendered, setRendered] = useState(open);
  const [closing, setClosing] = useState(false);

  useEffect(() => {
    if (open) {
      setRendered(true);
      setClosing(false);
    } else if (rendered) {
      setClosing(true);
    }
  }, [open, rendered]);

  if (!rendered) return null;

  return (
    <div
      className={`dialog-root${closing ? ' is-closing' : ''}`}
      onAnimationEnd={(event) => {
        if (closing && event.animationName === 'filter-dialog-out') {
          setRendered(false);
          setClosing(false);
        }
      }}
    >
      {children}
    </div>
  );
}
