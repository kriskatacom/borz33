import { useEffect, useId, useRef, useState, type DragEvent } from 'react';
import { createPortal } from 'react-dom';
import { ImagePlus, Trash2, Upload, ZoomIn } from 'lucide-react';
import { deleteUserAvatar, uploadUserAvatar, type ManagedUser } from '@/api/users';
import type { ProductImage } from '@/api/products';
import { Button } from '@/components/ui/Button';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { LabelWithHelp } from '@/components/ui/HelpHint';
import { ImageLightbox } from '@/features/products/ProductImagesSection';
import { toast, toastError } from '@/lib/toast';
import { cn } from '@/lib/utils';

const ACCEPT = 'image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp';
const MAX_BYTES = 8 * 1024 * 1024;
const ALLOWED_TYPES = new Set(['image/jpeg', 'image/jpg', 'image/png', 'image/webp']);

function isAllowedImage(file: File): boolean {
  if (file.type && ALLOWED_TYPES.has(file.type)) {
    return true;
  }

  return /\.(jpe?g|png|webp)$/i.test(file.name);
}

function validateFile(file: File): string | null {
  if (!isAllowedImage(file)) {
    return `${file.name}: разрешени са JPEG, PNG и WebP.`;
  }

  if (file.size > MAX_BYTES) {
    return `${file.name}: най-много 8 MB.`;
  }

  return null;
}

function lightboxImage(url: string, alt: string): ProductImage {
  return {
    id: 0,
    role: 'avatar',
    url,
    original_name: '',
    mime: '',
    size: 0,
    alt,
    sort_order: 0,
  };
}

export function UserAvatarField({
  userId,
  avatarUrl,
  displayName,
  token,
  onUserChange,
}: {
  userId: number | null;
  avatarUrl: string | null;
  displayName: string;
  token: string;
  onUserChange: (user: ManagedUser) => void;
}) {
  const inputId = useId();
  const inputRef = useRef<HTMLInputElement>(null);
  const abortRef = useRef<AbortController | null>(null);
  const previewRef = useRef<string | null>(null);
  const [progress, setProgress] = useState<number | null>(null);
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);
  const [over, setOver] = useState(false);
  const [confirm, setConfirm] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [lightbox, setLightbox] = useState(false);

  useEffect(() => {
    return () => {
      abortRef.current?.abort();
      if (previewRef.current) {
        URL.revokeObjectURL(previewRef.current);
      }
    };
  }, []);

  function pickFile() {
    inputRef.current?.click();
  }

  async function upload(file: File) {
    if (userId === null) {
      return;
    }

    const reason = validateFile(file);
    if (reason) {
      toast.error(reason);
      return;
    }

    abortRef.current?.abort();
    abortRef.current = new AbortController();
    if (previewRef.current) {
      URL.revokeObjectURL(previewRef.current);
    }
    const url = URL.createObjectURL(file);
    previewRef.current = url;
    setPreviewUrl(url);
    setProgress(0);

    try {
      const response = await uploadUserAvatar(token, userId, file, {
        signal: abortRef.current.signal,
        onProgress: setProgress,
      });
      onUserChange(response.data.user);
      toast.success(response.message || 'Профилната снимка е записана.');
    } catch (error) {
      if (error instanceof DOMException && error.name === 'AbortError') {
        return;
      }
      toastError(error, 'Качването не беше успешно.');
    } finally {
      if (previewRef.current) {
        URL.revokeObjectURL(previewRef.current);
        previewRef.current = null;
      }
      setPreviewUrl(null);
      setProgress(null);
    }
  }

  async function onRemove() {
    if (userId === null) {
      return;
    }

    setDeleting(true);
    try {
      const response = await deleteUserAvatar(token, userId);
      onUserChange(response.data.user);
      setConfirm(false);
      toast.success(response.message || 'Профилната снимка е премахната.');
    } catch (error) {
      toastError(error, 'Изтриването не беше успешно.');
    } finally {
      setDeleting(false);
    }
  }

  function onDrop(event: DragEvent) {
    event.preventDefault();
    event.stopPropagation();
    setOver(false);
    const file = event.dataTransfer.files[0];
    if (file) {
      void upload(file);
    }
  }

  if (userId === null) {
    return (
      <p className="m-0 text-base text-muted-foreground">
        Запишете профила, за да качите снимка.
      </p>
    );
  }

  const shown = previewUrl ?? avatarUrl ?? null;
  const busy = progress !== null;
  const alt = displayName.trim() || 'Профилна снимка';

  return (
    <div>
      <LabelWithHelp
        label="Профилна снимка"
        help="JPEG, PNG или WebP, до 8 MB. Сменянето заменя предишния файл."
      />
      {createPortal(
        <input
          ref={inputRef}
          id={inputId}
          type="file"
          accept={ACCEPT}
          tabIndex={-1}
          className="pointer-events-none fixed top-0 left-0 size-px opacity-0"
          onChange={(event) => {
            const file = event.target.files?.[0];
            if (file) {
              void upload(file);
            }
            event.target.value = '';
          }}
        />,
        document.body
      )}
      <div className="mt-2 flex flex-wrap items-start gap-3">
        <button
          type="button"
          className={cn(
            'relative size-32 shrink-0 overflow-hidden rounded-[6px] border border-dashed border-border bg-field p-0 transition-colors',
            over && 'border-primary bg-primary/6',
            shown && !busy && 'cursor-zoom-in'
          )}
          disabled={busy}
          onClick={() => {
            if (avatarUrl && !busy) {
              setLightbox(true);
              return;
            }
            pickFile();
          }}
          onDragOver={(event) => {
            event.preventDefault();
            setOver(true);
          }}
          onDragLeave={() => setOver(false)}
          onDrop={onDrop}
        >
          {shown ? (
            <img src={shown} alt={alt} className="size-full object-cover" />
          ) : (
            <span className="flex size-full flex-col items-center justify-center gap-1 text-muted-foreground">
              <ImagePlus className="size-5" aria-hidden />
              <span className="px-1 text-center text-xs">Качи</span>
            </span>
          )}
          {shown && !busy ? (
            <span className="absolute inset-x-0 bottom-0 flex justify-end bg-gradient-to-t from-foreground/70 to-transparent p-1.5">
              <span className="flex size-8 items-center justify-center rounded-[6px] bg-secondary text-secondary-foreground">
                <ZoomIn className="size-4" aria-hidden />
              </span>
            </span>
          ) : null}
          {busy ? (
            <span className="absolute inset-0 flex items-center justify-center bg-background/70 text-sm font-bold">
              {progress}%
            </span>
          ) : null}
        </button>
        <div className="flex flex-wrap gap-2">
          <Button
            type="button"
            variant="outline"
            size="sm"
            disabled={busy}
            onClick={(event) => {
              event.preventDefault();
              event.stopPropagation();
              pickFile();
            }}
          >
            <Upload />
            {shown ? 'Смени' : 'Избери'}
          </Button>
          {avatarUrl && !busy ? (
            <Button type="button" variant="outline" size="sm" onClick={() => setConfirm(true)}>
              <Trash2 />
              Премахни
            </Button>
          ) : null}
        </div>
      </div>
      {lightbox && avatarUrl ? (
        <ImageLightbox
          images={[lightboxImage(avatarUrl, alt)]}
          index={0}
          onIndex={() => undefined}
          onClose={() => setLightbox(false)}
        />
      ) : null}
      {confirm ? (
        <ConfirmDialog
          title="Премахване на снимка"
          message="Профилната снимка ще бъде изтрита."
          confirmLabel="Изтрий"
          busy={deleting}
          onConfirm={() => void onRemove()}
          onCancel={() => setConfirm(false)}
        />
      ) : null}
    </div>
  );
}
