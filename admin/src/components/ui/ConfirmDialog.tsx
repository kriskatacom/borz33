import { RotateCcw, Share2, Trash2, X } from 'lucide-react';
import { Button } from '@/components/ui/Button';

type ConfirmDialogProps = {
  title: string;
  message: string;
  confirmLabel: string;
  busy?: boolean;
  variant?: 'destructive' | 'default';
  onConfirm: () => void;
  onCancel: () => void;
};

export function ConfirmDialog({
  title,
  message,
  confirmLabel,
  busy = false,
  variant = 'destructive',
  onConfirm,
  onCancel,
}: ConfirmDialogProps) {
  const ConfirmIcon = variant === 'destructive' ? (confirmLabel === 'Възстанови' ? RotateCcw : Trash2) : Share2;

  return (
    <div className="dialog-root">
      <button type="button" className="dialog-backdrop" aria-label="Затвори" onClick={onCancel} />
      <div className="dialog" role="alertdialog" aria-labelledby="dialog-title" aria-describedby="dialog-body">
        <h2 id="dialog-title">{title}</h2>
        <p id="dialog-body">{message}</p>
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
