import { RotateCcw, Trash2, X } from 'lucide-react';
import { Button } from '@/components/ui/Button';

type ConfirmDialogProps = {
  title: string;
  message: string;
  confirmLabel: string;
  busy?: boolean;
  onConfirm: () => void;
  onCancel: () => void;
};

export function ConfirmDialog({
  title,
  message,
  confirmLabel,
  busy = false,
  onConfirm,
  onCancel,
}: ConfirmDialogProps) {
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
          <Button type="button" variant="destructive" disabled={busy} onClick={onConfirm}>
            {confirmLabel === 'Възстанови' ? <RotateCcw /> : <Trash2 />}
            {busy ? 'Моля, изчакайте…' : confirmLabel}
          </Button>
        </div>
      </div>
    </div>
  );
}
