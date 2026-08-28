import { useEffect, useMemo, useState } from 'react';
import { Images, X } from 'lucide-react';
import { ApiError } from '@/api/client';
import { listMedia, type MediaFile } from '@/api/media';
import { Button } from '@/components/ui/Button';
import { Field } from '@/components/ui/Field';
import { toast } from '@/lib/toast';
import { cn } from '@/lib/utils';

export function MediaPickerDialog({
  token,
  title = 'Избор от медията',
  multiple = false,
  onSelect,
  onClose,
}: {
  token: string;
  title?: string;
  multiple?: boolean;
  onSelect: (files: MediaFile[]) => void;
  onClose: () => void;
}) {
  const [search, setSearch] = useState('');
  const [q, setQ] = useState('');
  const [page, setPage] = useState(1);
  const [files, setFiles] = useState<MediaFile[]>([]);
  const [lastPage, setLastPage] = useState(1);
  const [busy, setBusy] = useState(true);
  const [selected, setSelected] = useState<Record<number, MediaFile>>({});

  const selectedList = useMemo(() => Object.values(selected), [selected]);

  useEffect(() => {
    const handle = window.setTimeout(() => {
      setQ(search.trim());
      setPage(1);
    }, 300);

    return () => window.clearTimeout(handle);
  }, [search]);

  useEffect(() => {
    let cancelled = false;

    async function load() {
      setBusy(true);
      try {
        const response = await listMedia(token, {
          q,
          kind: 'image',
          raster: true,
          page,
          per_page: 24,
        });
        if (cancelled) {
          return;
        }
        setFiles(response.data.files);
        setLastPage(response.data.pagination.last_page);
      } catch (error) {
        if (!cancelled) {
          toast.error(error instanceof ApiError ? error.message : 'Медията не можа да се зареди.');
          setFiles([]);
        }
      } finally {
        if (!cancelled) {
          setBusy(false);
        }
      }
    }

    void load();

    return () => {
      cancelled = true;
    };
  }, [page, q, token]);

  function toggle(file: MediaFile) {
    if (!multiple) {
      onSelect([file]);
      return;
    }

    setSelected((current) => {
      const next = { ...current };
      if (next[file.id]) {
        delete next[file.id];
      } else {
        next[file.id] = file;
      }
      return next;
    });
  }

  return (
    <div className="dialog-root">
      <button type="button" className="dialog-backdrop" aria-label="Затвори" onClick={onClose} />
      <div className="dialog dialog-wide" role="dialog" aria-modal="true" aria-labelledby="media-picker-title">
        <div className="mb-3 flex items-start justify-between gap-2">
          <h2 id="media-picker-title">{title}</h2>
          <Button type="button" size="icon" variant="outline" aria-label="Затвори" onClick={onClose}>
            <X />
          </Button>
        </div>
        <Field
          id="media-picker-q"
          label="Търсене"
          help="JPEG, PNG и WebP от библиотеката. Качените от продукти и профили също са тук."
          value={search}
          placeholder="Име на файл"
          onChange={(event) => setSearch(event.target.value)}
        />
        {files.length === 0 && !busy ? (
          <p className="muted-line">Няма изображения в медията.</p>
        ) : (
          <div className="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-4">
            {files.map((file) => (
              <button
                key={file.id}
                type="button"
                className={cn(
                  'overflow-hidden rounded-[6px] border border-border bg-muted p-0',
                  selected[file.id] && 'ring-[3px] ring-ring'
                )}
                onClick={() => toggle(file)}
              >
                <img src={file.url} alt={file.alt || file.original_name} className="aspect-square size-full object-cover" />
              </button>
            ))}
          </div>
        )}
        <div className="dialog-actions mt-4">
          {lastPage > 1 ? (
            <div className="mr-auto flex gap-2">
              <Button type="button" variant="outline" size="sm" disabled={page <= 1 || busy} onClick={() => setPage((current) => current - 1)}>
                Предишна
              </Button>
              <Button type="button" variant="outline" size="sm" disabled={page >= lastPage || busy} onClick={() => setPage((current) => current + 1)}>
                Следваща
              </Button>
            </div>
          ) : null}
          <Button type="button" variant="outline" onClick={onClose}>
            Отказ
          </Button>
          {multiple ? (
            <Button type="button" disabled={selectedList.length === 0} onClick={() => onSelect(selectedList)}>
              <Images />
              Избери {selectedList.length > 0 ? `(${selectedList.length})` : ''}
            </Button>
          ) : null}
        </div>
      </div>
    </div>
  );
}
