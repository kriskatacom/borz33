import { useEffect, useState } from 'react';
import { Check, Info, AlertTriangle, X } from 'lucide-react';
import { dismissToast, subscribeToasts, type ToastItem } from '@/lib/toast';
import { Button } from '@/components/ui/Button';

const icons = {
  success: Check,
  error: AlertTriangle,
  info: Info,
};

export function Toaster() {
  const [items, setItems] = useState<ToastItem[]>([]);

  useEffect(() => subscribeToasts(setItems), []);

  if (items.length === 0) {
    return null;
  }

  return (
    <div className="toast-stack" aria-live="polite" aria-relevant="additions">
      {items.map((item) => {
        const Icon = icons[item.kind];

        return (
          <div
            key={item.id}
            className={`toast is-${item.kind}`}
            role={item.kind === 'error' ? 'alert' : 'status'}
          >
            <span className="toast-icon" aria-hidden>
              <Icon className="size-4" />
            </span>
            <p className="toast-message">{item.message}</p>
            <Button
              type="button"
              size="icon"
              variant="ghost"
              className="size-8 shrink-0"
              aria-label="Затвори"
              onClick={() => dismissToast(item.id)}
            >
              <X />
            </Button>
          </div>
        );
      })}
    </div>
  );
}
