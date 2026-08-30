import { useEffect, useMemo, useRef, useState, type DragEvent } from 'react';
import { Check, File, FileText, Film, Grid2X2, Grid3X3, ImagePlus, Images, LayoutGrid, List, LoaderCircle, Music, Upload, X } from 'lucide-react';
import { ApiError } from '@/api/client';
import { listMediaCached, uploadMediaFile, type MediaFile } from '@/api/media';
import { Button } from '@/components/ui/Button';
import { Field } from '@/components/ui/Field';
import { mediaKindLabel, validateMediaFile } from '@/features/media/mediaFile';
import { formatBytes } from '@/lib/format';
import { toast, toastError } from '@/lib/toast';
import { cn } from '@/lib/utils';

type ViewMode = 'comfortable' | 'compact' | 'tiny' | 'list';
type Panel = 'library' | 'upload';

type PendingUpload = {
  key: string;
  name: string;
  progress: number;
};

const uploadDateFormatter = new Intl.DateTimeFormat('bg-BG', {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
  year: 'numeric',
});

function localDateKey(date: Date): string {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function uploadDateGroup(value: string | null): { key: string; label: string } {
  if (!value) return { key: 'unknown', label: 'Без дата на качване' };

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return { key: 'unknown', label: 'Без дата на качване' };

  const today = new Date();
  const yesterday = new Date(today);
  yesterday.setDate(today.getDate() - 1);
  const key = localDateKey(date);

  if (key === localDateKey(today)) return { key, label: 'Днес' };
  if (key === localDateKey(yesterday)) return { key, label: 'Вчера' };

  const label = uploadDateFormatter.format(date);
  return { key, label: label.charAt(0).toUpperCase() + label.slice(1) };
}

function paginationItems(page: number, lastPage: number): Array<number | 'ellipsis'> {
  if (lastPage <= 7) return Array.from({ length: lastPage }, (_, index) => index + 1);

  const items: Array<number | 'ellipsis'> = [1];
  if (page > 3) items.push('ellipsis');
  for (let current = Math.max(2, page - 1); current <= Math.min(lastPage - 1, page + 1); current += 1) {
    items.push(current);
  }
  if (page < lastPage - 2) items.push('ellipsis');
  items.push(lastPage);
  return items;
}

function KindIcon({ kind }: { kind: string }) {
  const className = 'size-8 text-muted-foreground';

  if (kind === 'video') {
    return <Film className={className} aria-hidden />;
  }

  if (kind === 'audio') {
    return <Music className={className} aria-hidden />;
  }

  if (kind === 'document') {
    return <FileText className={className} aria-hidden />;
  }

  return <File className={className} aria-hidden />;
}

export function MediaPickerDialog({
  token,
  title = 'Избор от медията',
  multiple = false,
  allFiles = false,
  onSelect,
  onClose,
}: {
  token: string;
  title?: string;
  multiple?: boolean;
  allFiles?: boolean;
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
  const [multiSelect, setMultiSelect] = useState(false);
  const [panel, setPanel] = useState<Panel>('library');
  const [view, setView] = useState<ViewMode>(() => {
    const saved = window.localStorage.getItem('media-picker-view');
    return saved === 'compact' || saved === 'tiny' || saved === 'list' ? saved : 'comfortable';
  });
  const [over, setOver] = useState(false);
  const [pending, setPending] = useState<PendingUpload[]>([]);
  const inputRef = useRef<HTMLInputElement>(null);
  const abortRef = useRef<AbortController | null>(null);
  const libraryRef = useRef<HTMLDivElement>(null);

  const selectedList = useMemo(() => Object.values(selected), [selected]);
  const groupedFiles = useMemo(() => {
    const groups: Array<{ key: string; label: string; files: MediaFile[] }> = [];
    for (const file of files) {
      const date = uploadDateGroup(file.created_at);
      const existing = groups.find((group) => group.key === date.key);
      if (existing) existing.files.push(file);
      else groups.push({ ...date, files: [file] });
    }
    return groups;
  }, [files]);
  const uploading = pending.length > 0;

  useEffect(() => () => abortRef.current?.abort(), []);

  function changeView(next: ViewMode) {
    setView(next);
    window.localStorage.setItem('media-picker-view', next);
  }

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
        const filters = {
          q,
          ...(allFiles ? {} : { kind: 'image', raster: true }),
          page,
          per_page: 24,
        };
        const response = await listMediaCached(token, filters);
        if (cancelled) {
          return;
        }
        setFiles(response.data.files);
        setLastPage(response.data.pagination.last_page);
        if (page < response.data.pagination.last_page) {
          void listMediaCached(token, { ...filters, page: page + 1 }).catch(() => undefined);
        }
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
  }, [allFiles, page, q, token]);

  function toggle(file: MediaFile) {
    setSelected((current) => {
      if (!multiple || !multiSelect) return { [file.id]: file };

      const next = { ...current };
      if (next[file.id]) {
        delete next[file.id];
      } else {
        next[file.id] = file;
      }
      return next;
    });
  }

  function toggleMultiSelect() {
    const next = !multiSelect;
    setMultiSelect(next);
    if (!next) {
      setSelected((selection) => {
        const first = Object.values(selection)[0];
        return first ? { [first.id]: first } : {};
      });
    }
  }

  async function uploadAll(incoming: File[]) {
    if (incoming.length === 0) return;

    abortRef.current?.abort();
    abortRef.current = new AbortController();
    const signal = abortRef.current.signal;
    let uploaded = 0;

    for (const file of incoming) {
      const reason = validateMediaFile(file);
      if (reason) {
        toast.error(reason);
        continue;
      }

      if (!allFiles && !file.type.startsWith('image/')) {
        toast.error(`${file.name}: тук могат да се качват само изображения.`);
        continue;
      }

      const key = `${file.name}-${file.size}-${file.lastModified}-${Math.random()}`;
      setPending((current) => [...current, { key, name: file.name, progress: 0 }]);

      try {
        await uploadMediaFile(token, file, {
          signal,
          onProgress: (progress) => setPending((current) => current.map((item) => item.key === key ? { ...item, progress } : item)),
        });
        uploaded += 1;
      } catch (error) {
        if (error instanceof DOMException && error.name === 'AbortError') return;
        toastError(error, `${file.name}: качването не беше успешно.`);
      } finally {
        setPending((current) => current.filter((item) => item.key !== key));
      }
    }

    if (uploaded > 0) {
      setSearch('');
      setQ('');
      setPage(1);
      setPanel('library');
      setBusy(true);
      try {
        const response = await listMediaCached(token, {
          ...(allFiles ? {} : { kind: 'image', raster: true }),
          page: 1,
          per_page: 24,
        });
        setFiles(response.data.files);
        setLastPage(response.data.pagination.last_page);
        toast.success(uploaded === 1 ? 'Файлът е качен и е готов за избор.' : `${uploaded} файла са качени и са готови за избор.`);
      } catch (error) {
        toastError(error, 'Файловете са качени, но библиотеката не можа да се обнови.');
      } finally {
        setBusy(false);
      }
    }
  }

  function onDrop(event: DragEvent<HTMLButtonElement>) {
    event.preventDefault();
    event.stopPropagation();
    setOver(false);
    void uploadAll(Array.from(event.dataTransfer.files));
  }

  function fileContent(file: MediaFile) {
    if (file.kind === 'image') {
      return (
        <img
          src={file.url}
          alt={file.alt || file.original_name}
          className="size-full object-cover"
          width="320"
          height="320"
          loading="lazy"
          decoding="async"
          fetchPriority="low"
          draggable={false}
        />
      );
    }

    return <KindIcon kind={file.kind} />;
  }

  function goToPage(nextPage: number) {
    if (nextPage === page || nextPage < 1 || nextPage > lastPage) return;
    setPage(nextPage);
    window.requestAnimationFrame(() => libraryRef.current?.scrollIntoView({ block: 'start' }));
  }

  return (
    <div className="dialog-root">
      <button type="button" className="dialog-backdrop" aria-label="Затвори" onClick={onClose} />
      <div className="dialog dialog-wide media-picker-dialog" role="dialog" aria-modal="true" aria-labelledby="media-picker-title">
        <div className="mb-3 flex items-start justify-between gap-2">
          <h2 id="media-picker-title">{title}</h2>
          <Button type="button" size="icon" variant="outline" aria-label="Затвори" onClick={onClose}>
            <X />
          </Button>
        </div>
        <input
          ref={inputRef}
          className="sr-only"
          type="file"
          multiple
          accept={allFiles ? undefined : 'image/jpeg,image/png,image/webp,image/gif'}
          onChange={(event) => {
            void uploadAll(Array.from(event.target.files ?? []));
            event.target.value = '';
          }}
        />
        <div className="mb-4 flex gap-1 border-b border-border" role="tablist" aria-label="Медийни файлове">
          <button type="button" role="tab" aria-selected={panel === 'library'} className={cn('border-b-2 px-4 py-2 font-medium transition-colors', panel === 'library' ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground')} onClick={() => setPanel('library')}>Библиотека</button>
          <button type="button" role="tab" aria-selected={panel === 'upload'} className={cn('border-b-2 px-4 py-2 font-medium transition-colors', panel === 'upload' ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground')} onClick={() => setPanel('upload')}><Upload className="mr-2 inline size-4" aria-hidden />Качване</button>
        </div>
        {panel === 'library' ? (
          <div role="tabpanel" className="min-h-0 flex-1">
            <Field
              id="media-picker-q"
              label="Търсене"
              help={allFiles ? 'Всички файлове от библиотеката.' : 'Изображенията от библиотеката.'}
              value={search}
              placeholder="Име на файл"
              onChange={(event) => setSearch(event.target.value)}
            />
            <div className="mt-2 flex flex-wrap items-center gap-2">
              <div className="flex items-center gap-1" role="group" aria-label="Изглед на файловете">
                <Button type="button" size="icon" variant={view === 'comfortable' ? 'secondary' : 'outline'} aria-label="Големи плочки" aria-pressed={view === 'comfortable'} onClick={() => changeView('comfortable')}><Grid2X2 /></Button>
                <Button type="button" size="icon" variant={view === 'compact' ? 'secondary' : 'outline'} aria-label="Компактни плочки" aria-pressed={view === 'compact'} onClick={() => changeView('compact')}><LayoutGrid /></Button>
                <Button type="button" size="icon" variant={view === 'tiny' ? 'secondary' : 'outline'} aria-label="Много малки плочки" aria-pressed={view === 'tiny'} onClick={() => changeView('tiny')}><Grid3X3 /></Button>
                <Button type="button" size="icon" variant={view === 'list' ? 'secondary' : 'outline'} aria-label="Списък" aria-pressed={view === 'list'} onClick={() => changeView('list')}><List /></Button>
              </div>
              {multiple ? (
                <Button type="button" size="sm" variant={multiSelect ? 'secondary' : 'outline'} aria-pressed={multiSelect} onClick={toggleMultiSelect}><Images />Избери повече</Button>
              ) : null}
            </div>
            <div ref={libraryRef} />
            <div className="relative min-h-40" aria-busy={busy}>
              {busy ? (
                <div className="sticky top-0 z-20 mt-3 flex min-h-12 items-center justify-center gap-2 border border-border bg-card/95 px-4 text-sm font-medium shadow-sm backdrop-blur" role="status" aria-live="polite">
                  <LoaderCircle className="size-5 animate-spin text-primary" aria-hidden />
                  Зареждане на файловете…
                </div>
              ) : null}
              <div className={cn('transition-opacity', busy && 'pointer-events-none opacity-45')}>
                {files.length === 0 && !busy ? (
                  <p className="muted-line">{allFiles ? 'Няма файлове в медията.' : 'Няма изображения в медията.'}</p>
                ) : (
                  <div className="mt-3 grid gap-4">
            {groupedFiles.map((group) => (
              <section key={group.key} aria-labelledby={`media-date-${group.key}`}>
                <div className="mb-2 flex items-center gap-3">
                  <h3 id={`media-date-${group.key}`} className="m-0 text-sm font-semibold text-foreground">{group.label}</h3>
                  <span className="h-px flex-1 bg-border" aria-hidden />
                  <span className="text-xs text-muted-foreground">{group.files.length}</span>
                </div>
                <div className={cn(
                  view === 'comfortable' && 'grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4',
                  view === 'compact' && 'grid grid-cols-4 gap-1 sm:grid-cols-6',
                  view === 'tiny' && 'grid grid-cols-6 gap-1 sm:grid-cols-8 md:grid-cols-10 lg:grid-cols-12',
                  view === 'list' && 'grid gap-1'
                )}>
                  {group.files.map((file) => (
                    <button
                      key={file.id}
                      type="button"
                      className={cn(
                        'media-picker-file relative overflow-hidden border border-border bg-muted p-0 text-left',
                        view === 'comfortable' && 'grid bg-card',
                        view === 'compact' && 'aspect-square',
                        view === 'tiny' && 'aspect-square',
                        view === 'list' && 'grid min-h-16 grid-cols-[4rem_minmax(0,1fr)_auto] items-center gap-3 bg-card pr-3',
                        selected[file.id] && 'ring-[3px] ring-ring'
                      )}
                      onClick={() => toggle(file)}
                    >
                      {selected[file.id] ? (
                        <span className="absolute top-1.5 right-1.5 z-10 flex size-7 items-center justify-center rounded-full bg-primary text-primary-foreground shadow" aria-hidden><Check className="size-4" /></span>
                      ) : null}
                      <span className={cn('flex items-center justify-center overflow-hidden', view === 'comfortable' && 'aspect-square', (view === 'compact' || view === 'tiny') && 'size-full', view === 'list' && 'aspect-square size-16')}>{fileContent(file)}</span>
                      {view === 'comfortable' ? (
                        <span className="grid gap-0.5 p-2"><span className="truncate text-sm font-medium">{file.original_name}</span><span className="text-xs text-muted-foreground">{mediaKindLabel(file.kind)} · {formatBytes(file.size)}</span></span>
                      ) : null}
                      {view === 'list' ? (
                        <><span className="min-w-0"><span className="block truncate font-medium">{file.original_name}</span><span className="block text-sm text-muted-foreground">{mediaKindLabel(file.kind)} · {formatBytes(file.size)}</span></span><span className="text-sm text-muted-foreground">Избери</span></>
                      ) : null}
                    </button>
                  ))}
                </div>
              </section>
            ))}
                  </div>
                )}
              </div>
            </div>
          </div>
        ) : (
          <div role="tabpanel" className="grid min-h-[min(32rem,60vh)] content-center">
            <button
              type="button"
              className={cn(
                'flex min-h-64 w-full flex-col items-center justify-center gap-2 border border-dashed border-border bg-field px-6 py-10 text-center transition-colors',
                over && 'border-primary bg-primary/6'
              )}
              disabled={uploading}
              onClick={() => inputRef.current?.click()}
              onDragOver={(event) => { event.preventDefault(); setOver(true); }}
              onDragLeave={() => setOver(false)}
              onDrop={onDrop}
            >
              <ImagePlus className="size-10 text-muted-foreground" aria-hidden />
              <span className="text-lg font-bold">Пуснете файлове тук или изберете от устройството</span>
              <span className="text-sm text-muted-foreground">До 32 MB на файл{allFiles ? '' : ' · JPEG, PNG, WebP или GIF'}</span>
            </button>
            {pending.length > 0 ? (
              <ul className="mt-4 grid list-none gap-2 p-0" aria-live="polite">
                {pending.map((item) => (
                  <li key={item.key} className="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 text-sm">
                    <span className="truncate">{item.name}</span>
                    <span className="text-muted-foreground">{item.progress}%</span>
                    <span className="col-span-2 h-1 overflow-hidden bg-muted"><span className="block h-full bg-primary transition-[width]" style={{ width: `${item.progress}%` }} /></span>
                  </li>
                ))}
              </ul>
            ) : null}
          </div>
        )}
        <div className="media-picker-footer">
          {panel === 'library' && lastPage > 1 ? (
            <nav className="flex flex-wrap items-center gap-1" aria-label="Страници на медийната библиотека">
              <Button type="button" variant="outline" size="sm" disabled={page <= 1 || busy} onClick={() => goToPage(page - 1)}>Предишна</Button>
              {paginationItems(page, lastPage).map((item, index) => item === 'ellipsis' ? (
                <span key={`ellipsis-${index}`} className="flex size-8 items-center justify-center text-muted-foreground" aria-hidden>…</span>
              ) : (
                <Button key={item} type="button" variant={item === page ? 'secondary' : 'outline'} size="sm" className="min-w-8 px-2" aria-label={`Страница ${item}`} aria-current={item === page ? 'page' : undefined} disabled={busy} onClick={() => goToPage(item)}>{item}</Button>
              ))}
              <Button type="button" variant="outline" size="sm" disabled={page >= lastPage || busy} onClick={() => goToPage(page + 1)}>Следваща</Button>
            </nav>
          ) : null}
          <div className="ml-auto flex items-center justify-end gap-2">
            <Button type="button" variant="outline" onClick={onClose}>Отказ</Button>
            <Button type="button" disabled={selectedList.length === 0} onClick={() => onSelect(selectedList)}>
              {multiple && multiSelect ? <Images /> : <Check />}
              {multiple && multiSelect ? `Избери файловете (${selectedList.length})` : 'Избери файла'}
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
}
