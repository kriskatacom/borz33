import { useEffect, useId, useMemo, useRef, useState, type DragEvent, type FormEvent } from 'react';
import { createPortal } from 'react-dom';
import { useSearchParams } from 'react-router-dom';
import {
  ChevronLeft,
  ChevronRight,
  Copy,
  Download,
  File,
  FilePenLine,
  FileText,
  Film,
  ImagePlus,
  Music,
  Trash2,
  Upload,
  ZoomIn,
} from 'lucide-react';
import { ApiError } from '@/api/client';
import {
  deleteMediaFile,
  listMedia,
  updateMediaFile,
  uploadMediaFile,
  type MediaFile,
} from '@/api/media';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { DATA_TABLE_PAGE_SIZES, DEFAULT_PAGE_SIZE, scrollPageToTop } from '@/components/data-table/DataTable';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { MediaGridSkeleton } from '@/components/admin-page-skeleton';
import { Button } from '@/components/ui/Button';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Field } from '@/components/ui/Field';
import { LabelWithHelp } from '@/components/ui/HelpHint';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { MediaLightbox } from '@/features/media/MediaLightbox';
import { mediaKindLabel, validateMediaFile } from '@/features/media/mediaFile';
import { formatBytes, formatDateTime } from '@/lib/format';
import { toast, toastError } from '@/lib/toast';
import { cn } from '@/lib/utils';

type PendingUpload = {
  key: string;
  name: string;
  progress: number;
};

type PreviewState = {
  kind: string;
  index: number;
};

function parsePageSize(raw: string | null): number {
  const value = Number(raw);

  return (DATA_TABLE_PAGE_SIZES as readonly number[]).includes(value) ? value : DEFAULT_PAGE_SIZE;
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

function getPageItems(page: number, lastPage: number): Array<number | 'ellipsis'> {
  if (lastPage <= 7) {
    return Array.from({ length: lastPage }, (_, index) => index + 1);
  }

  const items: Array<number | 'ellipsis'> = [1];

  if (page > 3) {
    items.push('ellipsis');
  }

  for (let current = Math.max(2, page - 1); current <= Math.min(lastPage - 1, page + 1); current++) {
    items.push(current);
  }

  if (page < lastPage - 2) {
    items.push('ellipsis');
  }

  items.push(lastPage);

  return items;
}

export function MediaPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const [params, setParams] = useSearchParams();
  const [search, setSearch] = useState(params.get('q') ?? '');
  const [files, setFiles] = useState<MediaFile[]>([]);
  const [total, setTotal] = useState(0);
  const [lastPage, setLastPage] = useState(1);
  const [busy, setBusy] = useState(true);
  const [message, setMessage] = useState<string | null>(null);
  const [over, setOver] = useState(false);
  const [pending, setPending] = useState<PendingUpload[]>([]);
  const [confirm, setConfirm] = useState<MediaFile | null>(null);
  const [deleting, setDeleting] = useState(false);
  const [preview, setPreview] = useState<PreviewState | null>(null);
  const [details, setDetails] = useState<MediaFile | null>(null);
  const [detailName, setDetailName] = useState('');
  const [detailAlt, setDetailAlt] = useState('');
  const [savingDetails, setSavingDetails] = useState(false);
  const inputId = useId();
  const inputRef = useRef<HTMLInputElement>(null);
  const abortRef = useRef<AbortController | null>(null);
  useGlobalLoading(busy && pending.length === 0);

  const filters = useMemo(
    () => ({
      q: params.get('q') ?? '',
      kind: params.get('kind') ?? '',
      page: Number(params.get('page') ?? '1') || 1,
      per_page: parsePageSize(params.get('per_page')),
    }),
    [params]
  );

  const previewFiles = preview ? files.filter((file) => file.kind === preview.kind) : [];

  function updateParams(next: Record<string, string>, resetPage = true) {
    const merged = new URLSearchParams(params);

    for (const [key, value] of Object.entries(next)) {
      if (value) {
        merged.set(key, value);
      } else {
        merged.delete(key);
      }
    }

    if (resetPage) {
      merged.delete('page');
    }

    setParams(merged);
  }

  useEffect(() => {
    const handle = window.setTimeout(() => {
      if (search !== (params.get('q') ?? '')) {
        updateParams({ q: search });
      }
    }, 300);

    return () => window.clearTimeout(handle);
  }, [search, params]);

  useEffect(() => {
    let cancelled = false;

    async function load() {
      setBusy(true);
      setMessage(null);

      try {
        const response = await listMedia(token, filters);
        if (cancelled) {
          return;
        }
        setFiles(response.data.files);
        setTotal(response.data.pagination.total);
        setLastPage(response.data.pagination.last_page);
      } catch (error) {
        if (!cancelled) {
          const text = error instanceof ApiError ? error.message : 'Списъкът не можа да се зареди.';
          setMessage(text);
          toast.error(text);
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
  }, [filters, token]);

  useEffect(() => {
    return () => abortRef.current?.abort();
  }, []);

  async function reload() {
    const response = await listMedia(token, filters);
    setFiles(response.data.files);
    setTotal(response.data.pagination.total);
    setLastPage(response.data.pagination.last_page);
  }

  async function uploadAll(selected: File[]) {
    if (selected.length === 0) {
      return;
    }

    abortRef.current?.abort();
    abortRef.current = new AbortController();
    const signal = abortRef.current.signal;

    for (const file of selected) {
      const reason = validateMediaFile(file);
      if (reason) {
        toast.error(reason);
        continue;
      }

      const key = `${file.name}-${file.size}-${file.lastModified}-${Math.random()}`;
      setPending((current) => [...current, { key, name: file.name, progress: 0 }]);

      try {
        const response = await uploadMediaFile(token, file, {
          signal,
          onProgress: (percent) => {
            setPending((current) => current.map((item) => (item.key === key ? { ...item, progress: percent } : item)));
          },
        });
        toast.success(response.message || 'Файлът е качен.');
      } catch (error) {
        if (error instanceof DOMException && error.name === 'AbortError') {
          return;
        }
        toastError(error, `${file.name}: качването не беше успешно.`);
      } finally {
        setPending((current) => current.filter((item) => item.key !== key));
      }
    }

    try {
      await reload();
    } catch (error) {
      toastError(error, 'Списъкът не можа да се обнови.');
    }
  }

  function onDrop(event: DragEvent) {
    event.preventDefault();
    event.stopPropagation();
    setOver(false);
    void uploadAll(Array.from(event.dataTransfer.files));
  }

  async function onDelete() {
    if (!confirm) {
      return;
    }

    setDeleting(true);
    try {
      const response = await deleteMediaFile(token, confirm.id);
      toast.success(response.message || 'Файлът е изтрит.');
      setConfirm(null);
      setDetails((current) => (current?.id === confirm.id ? null : current));
      await reload();
    } catch (error) {
      toastError(error, 'Изтриването не беше успешно.');
    } finally {
      setDeleting(false);
    }
  }

  function openDetails(file: MediaFile) {
    setDetails(file);
    setDetailName(file.original_name);
    setDetailAlt(file.alt ?? '');
  }

  async function onSaveDetails(event: FormEvent) {
    event.preventDefault();
    if (!details) {
      return;
    }

    setSavingDetails(true);

    try {
      const response = await updateMediaFile(token, details.id, {
        original_name: detailName.trim() === '' ? details.original_name : detailName.trim(),
        alt: detailAlt.trim() === '' ? null : detailAlt.trim(),
      });
      const next = response.data.file;
      openDetails(next);
      setFiles((current) => current.map((file) => (file.id === next.id ? next : file)));
      toast.success(response.message || 'Файлът е обновен.');
    } catch (error) {
      toastError(error, 'Записът не беше успешен.');
    } finally {
      setSavingDetails(false);
    }
  }

  async function copyUrl(file: MediaFile) {
    try {
      await navigator.clipboard.writeText(`${window.location.origin}${file.url}`);
      toast.success('Адресът е копиран.');
    } catch {
      toast.error('Адресът не можа да се копира.');
    }
  }

  function openPreview(file: MediaFile) {
    if (file.kind === 'image' || file.kind === 'video' || file.kind === 'audio') {
      const group = files.filter((item) => item.kind === file.kind);
      const index = group.findIndex((item) => item.id === file.id);
      setPreview({ kind: file.kind, index: Math.max(0, index) });
      return;
    }

    openDetails(file);
  }

  function goToPage(page: number) {
    if (page === filters.page) {
      return;
    }

    updateParams({ page: String(page) }, false);
    scrollPageToTop();
  }

  const uploading = pending.length > 0;

  return (
    <div className="page">
      <PageHeader
        title="Медия"
        help="Библиотека с файлове за магазина. Качвайте изображения, документи и други формати. Изпълними и уеб скриптове не се приемат."
        crumbs={[
          { label: 'Табло', to: routes.home },
          { label: 'Медия' },
        ]}
      />

      {createPortal(
        <input
          ref={inputRef}
          id={inputId}
          type="file"
          multiple
          tabIndex={-1}
          className="pointer-events-none fixed top-0 left-0 size-px opacity-0"
          onChange={(event) => {
            void uploadAll(Array.from(event.target.files ?? []));
            event.target.value = '';
          }}
        />,
        document.body
      )}

      <button
        type="button"
        className={cn(
          'mb-3 flex min-h-32 w-full flex-col items-center justify-center gap-2 rounded-[6px] border border-dashed border-border bg-field px-4 py-6 text-center transition-colors',
          over && 'border-primary bg-primary/6'
        )}
        disabled={uploading}
        onClick={() => inputRef.current?.click()}
        onDragOver={(event) => {
          event.preventDefault();
          setOver(true);
        }}
        onDragLeave={() => setOver(false)}
        onDrop={onDrop}
      >
        <ImagePlus className="size-6 text-muted-foreground" aria-hidden />
        <span className="font-bold">Пуснете файлове тук или изберете</span>
        <span className="text-sm text-muted-foreground">До 32 MB на файл. PHP, HTML и изпълними файлове не се приемат.</span>
      </button>

      {pending.length > 0 ? (
        <ul className="m-0 mb-3 list-none p-0">
          {pending.map((item) => (
            <li key={item.key} className="mb-1 font-sans text-sm text-muted-foreground">
              {item.name} — {item.progress}%
            </li>
          ))}
        </ul>
      ) : null}

      <form className="filters" onSubmit={(event) => event.preventDefault()}>
        <Field
          id="q"
          label="Търсене"
          help="Търси по име на файла, разширение или MIME тип."
          value={search}
          placeholder="Име на файл"
          onChange={(event) => setSearch(event.target.value)}
        />
        <div className="field">
          <LabelWithHelp htmlFor="kind" label="Тип" help="Филтър по вид файл. Изображенията ще могат да се избират и от продуктовите форми." />
          <Select
            value={filters.kind || 'all'}
            onValueChange={(value) => updateParams({ kind: value === 'all' ? '' : value })}
          >
            <SelectTrigger id="kind" className="w-full min-h-12 font-sans">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Всички типове</SelectItem>
              <SelectItem value="image">Изображения</SelectItem>
              <SelectItem value="video">Видео</SelectItem>
              <SelectItem value="audio">Аудио</SelectItem>
              <SelectItem value="document">Документи</SelectItem>
              <SelectItem value="other">Други</SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div className="field flex min-h-12 items-end">
          <Button type="button" variant="outline" disabled={uploading} onClick={() => inputRef.current?.click()}>
            <Upload />
            Качи файлове
          </Button>
        </div>
      </form>

      {message ? (
        <p className="form-message is-error" role="alert">
          {message}
        </p>
      ) : null}

      {busy && files.length === 0 ? <MediaGridSkeleton /> : files.length === 0 ? (
        <p className="muted-line">Няма файлове за избраните филтри.</p>
      ) : (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
          {files.map((file) => (
            <article key={file.id} className="overflow-hidden rounded-[6px] border border-border bg-card">
              <button
                type="button"
                className="relative flex aspect-square w-full items-center justify-center overflow-hidden bg-muted p-0"
                onClick={() => openPreview(file)}
              >
                {file.kind === 'image' ? (
                  <img src={file.url} alt={file.alt || file.original_name} className="size-full object-cover" />
                ) : (
                  <KindIcon kind={file.kind} />
                )}
                {file.kind === 'image' || file.kind === 'video' ? (
                  <span className="absolute right-1.5 bottom-1.5 flex size-8 items-center justify-center rounded-[6px] bg-secondary text-secondary-foreground">
                    <ZoomIn className="size-4" aria-hidden />
                  </span>
                ) : null}
              </button>
              <div className="grid gap-1 p-2">
                <button
                  type="button"
                  className="m-0 truncate text-left font-sans text-sm font-bold text-foreground hover:underline"
                  title={file.original_name}
                  onClick={() => openDetails(file)}
                >
                  {file.original_name}
                </button>
                <p className="m-0 font-sans text-xs text-muted-foreground">
                  {mediaKindLabel(file.kind)} · {formatBytes(file.size)}
                </p>
                <div className="flex flex-wrap gap-1">
                  <Button type="button" size="icon" variant="outline" aria-label="Детайли" onClick={() => openDetails(file)}>
                    <FilePenLine />
                  </Button>
                  <Button type="button" size="icon" variant="outline" aria-label="Изтрий" onClick={() => setConfirm(file)}>
                    <Trash2 />
                  </Button>
                </div>
              </div>
            </article>
          ))}
        </div>
      )}

      {total > 0 ? (
        <div className="pager mt-4">
          <p className="m-0 font-sans text-sm text-muted-foreground">
            Страница {filters.page} от {Math.max(1, lastPage)} · {total} записа
          </p>
          <div className="flex flex-wrap items-center gap-3">
            <label className="flex items-center gap-2 font-sans text-sm text-muted-foreground">
              <span>На страница</span>
              <Select
                value={String(filters.per_page)}
                disabled={busy}
                onValueChange={(value) => {
                  const nextSize = Number(value);
                  if (nextSize === filters.per_page) {
                    return;
                  }
                  updateParams({ per_page: nextSize === DEFAULT_PAGE_SIZE ? '' : String(nextSize) });
                  scrollPageToTop();
                }}
              >
                <SelectTrigger id="page-size" size="sm" className="w-[4.75rem] min-h-9 font-sans" aria-label="Записи на страница">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {DATA_TABLE_PAGE_SIZES.map((size) => (
                    <SelectItem key={size} value={String(size)}>
                      {size}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </label>
            <div className="flex flex-wrap items-center gap-1">
              <Button type="button" variant="outline" size="sm" disabled={filters.page <= 1 || busy} onClick={() => goToPage(filters.page - 1)}>
                <ChevronLeft />
                Предишна страница
              </Button>
              {getPageItems(filters.page, lastPage).map((item, index) =>
                item === 'ellipsis' ? (
                  <span key={`e-${index}`} className="px-1 text-muted-foreground">
                    …
                  </span>
                ) : (
                  <Button
                    key={item}
                    type="button"
                    size="sm"
                    variant={item === filters.page ? 'default' : 'outline'}
                    disabled={busy || item === filters.page}
                    aria-current={item === filters.page ? 'page' : undefined}
                    onClick={() => goToPage(item)}
                  >
                    {item}
                  </Button>
                )
              )}
              <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={filters.page >= lastPage || busy || lastPage <= 1}
                onClick={() => goToPage(filters.page + 1)}
              >
                Следваща страница
                <ChevronRight />
              </Button>
            </div>
          </div>
        </div>
      ) : null}

      {preview ? (
        <MediaLightbox
          files={previewFiles}
          index={preview.index}
          onIndex={(index) => setPreview({ kind: preview.kind, index })}
          onClose={() => setPreview(null)}
        />
      ) : null}

      {details ? (
        <div className="dialog-root">
          <button type="button" className="dialog-backdrop" aria-label="Затвори" onClick={() => setDetails(null)} />
          <div className="dialog media-details-dialog" role="dialog" aria-modal="true" aria-labelledby="media-details-title">
            <h2 id="media-details-title">Детайли</h2>
            {details.kind === 'image' ? (
              <img
                src={details.url}
                alt={details.alt || details.original_name}
                className="mb-3 max-h-48 w-full rounded-[6px] object-contain"
              />
            ) : null}
            <form className="grid gap-3" onSubmit={(event) => void onSaveDetails(event)}>
              <Field
                id="original_name"
                label="Име"
                help="Показвано име. Файлът на диска не се преименува."
                value={detailName}
                onChange={(event) => setDetailName(event.target.value)}
              />
              {details.kind === 'image' ? (
                <Field
                  id="alt"
                  label="Алтернативен текст"
                  help="Описание за достъпност. Ще се ползва при избор на изображение от медията."
                  value={detailAlt}
                  onChange={(event) => setDetailAlt(event.target.value)}
                />
              ) : null}
              <p className="m-0 font-sans text-sm text-muted-foreground">
                {mediaKindLabel(details.kind)} · {formatBytes(details.size)} · {details.mime}
                {details.created_at ? ` · ${formatDateTime(details.created_at)}` : ''}
              </p>
              <div className="mt-1 flex flex-col gap-3 border-t border-border pt-4 sm:flex-row sm:items-center">
                <div className="flex flex-wrap gap-2">
                  <Button type="button" variant="outline" onClick={() => void copyUrl(details)}>
                    <Copy />
                    Копирай адрес
                  </Button>
                  <Button type="button" variant="outline" asChild>
                    <a href={details.url} download={details.original_name}>
                      <Download />
                      Изтегли
                    </a>
                  </Button>
                </div>
                <Button type="submit" className="sm:ml-auto" disabled={savingDetails}>
                  {savingDetails ? 'Запис…' : 'Запази'}
                </Button>
              </div>
            </form>
          </div>
        </div>
      ) : null}

      {confirm ? (
        <ConfirmDialog
          title="Изтриване на файл"
          message={`Да изтрием ли „${confirm.original_name}“? Файлът се маха от диска и от продуктите или профилите, които го ползват.`}
          confirmLabel="Изтрий"
          busy={deleting}
          onConfirm={() => void onDelete()}
          onCancel={() => setConfirm(null)}
        />
      ) : null}
    </div>
  );
}
