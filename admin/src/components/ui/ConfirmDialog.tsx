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
          <button type="button" className="btn btn-ghost" disabled={busy} onClick={onCancel}>
            Отказ
          </button>
          <button type="button" className="btn btn-danger" disabled={busy} onClick={onConfirm}>
            {busy ? 'Моля, изчакайте…' : confirmLabel}
          </button>
        </div>
      </div>
    </div>
  );
}
