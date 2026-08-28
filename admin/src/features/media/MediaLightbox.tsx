import { useEffect } from 'react';
import { ChevronLeft, ChevronRight, X } from 'lucide-react';
import type { MediaFile } from '@/api/media';
import { Button } from '@/components/ui/Button';

export function MediaLightbox({
  files,
  index,
  onIndex,
  onClose,
}: {
  files: MediaFile[];
  index: number;
  onIndex: (index: number) => void;
  onClose: () => void;
}) {
  const file = files[index];

  useEffect(() => {
    function onKey(event: KeyboardEvent) {
      if (event.key === 'Escape') {
        onClose();
      }
      if (event.key === 'ArrowLeft' && files.length > 1) {
        onIndex((index - 1 + files.length) % files.length);
      }
      if (event.key === 'ArrowRight' && files.length > 1) {
        onIndex((index + 1) % files.length);
      }
    }

    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [files.length, index, onClose, onIndex]);

  if (!file) {
    return null;
  }

  return (
    <div className="dialog-root">
      <button type="button" className="dialog-backdrop" aria-label="Затвори" onClick={onClose} />
      <div
        className="fixed inset-4 z-50 m-auto flex max-h-[calc(100%-2rem)] max-w-5xl flex-col gap-3 rounded-[12px] border border-border bg-card p-3 shadow-lg"
        role="dialog"
        aria-modal="true"
        aria-label="Преглед"
      >
        <div className="flex items-center justify-between gap-2">
          <p className="m-0 truncate text-base font-bold">{file.original_name}</p>
          <Button type="button" size="icon" variant="outline" aria-label="Затвори" onClick={onClose}>
            <X />
          </Button>
        </div>
        <div className="relative min-h-0 flex-1 overflow-hidden rounded-[6px] bg-muted">
          {file.kind === 'image' ? (
            <img src={file.url} alt={file.alt || file.original_name} className="mx-auto max-h-[70vh] w-auto object-contain" />
          ) : file.kind === 'video' ? (
            <video src={file.url} controls className="mx-auto max-h-[70vh] w-full" />
          ) : file.kind === 'audio' ? (
            <audio src={file.url} controls className="w-full" />
          ) : (
            <p className="m-0 p-6 text-center text-muted-foreground">Няма преглед за този тип файл.</p>
          )}
        </div>
        {files.length > 1 ? (
          <div className="flex items-center justify-between gap-2">
            <Button type="button" variant="outline" onClick={() => onIndex((index - 1 + files.length) % files.length)}>
              <ChevronLeft />
              Предишен
            </Button>
            <span className="text-base text-muted-foreground">
              {index + 1} / {files.length}
            </span>
            <Button type="button" variant="outline" onClick={() => onIndex((index + 1) % files.length)}>
              <ChevronRight />
              Следващ
            </Button>
          </div>
        ) : null}
      </div>
    </div>
  );
}
