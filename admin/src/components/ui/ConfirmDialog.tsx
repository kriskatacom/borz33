import { useEffect } from 'react';
import { Check, RotateCcw, Trash2, X, type LucideIcon } from 'lucide-react';
import { Button } from '@/components/ui/Button';

type ConfirmDialogProps = {
  title: string;
  message: string;
  description?: string;
  confirmLabel: string;
  busy?: boolean;
  variant?: 'destructive' | 'default';
  confirmIcon?: LucideIcon;
  onConfirm: () => void;
  onCancel: () => void;
};

export function ConfirmDialog({
  title,
  message,
  description,
  confirmLabel,
  busy = false,
  variant = 'destructive',
  confirmIcon,
  onConfirm,
  onCancel,
}: ConfirmDialogProps) {
  const ConfirmIcon = confirmIcon ?? (variant === 'destructive' ? (confirmLabel === 'Възстанови' ? RotateCcw : Trash2) : Check);

  useEffect(() => {
    function onKeyDown(event: KeyboardEvent) {
      if (event.key === 'Escape' && !busy) onCancel();
    }

    document.addEventListener('keydown', onKeyDown);
    return () => document.removeEventListener('keydown', onKeyDown);
  }, [busy, onCancel]);

  return (
    <div className="dialog-root">
      <button type="button" className="dialog-backdrop" aria-label="Затвори" onClick={onCancel} />
      <div className="dialog dialog-confirmation" role="alertdialog" aria-modal="true" aria-labelledby="dialog-title" aria-describedby={description ? 'dialog-body dialog-description' : 'dialog-body'}>
        <h2 id="dialog-title">{title}</h2>
        <p id="dialog-body">{message}</p>
        {description ? <p id="dialog-description">{description}</p> : null}
        <div className="dialog-actions">
          <Button type="button" variant="outline" disabled={busy} onClick={onCancel}>
            <X />
            Отказ
          </Button>
          <Button type="button" variant={variant} disabled={busy} onClick={onConfirm}>
            <ConfirmIcon />
            {busy ? 'Моля, изчакайте…' : confirmLabel}
          </Button>
        </div>
      </div>
    </div>
  );
}
