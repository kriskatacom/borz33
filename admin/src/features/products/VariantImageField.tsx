import { useEffect, useId, useRef, useState, type DragEvent } from 'react';
import { createPortal } from 'react-dom';
import { FolderOpen, ImagePlus, Trash2, Upload, ZoomIn } from 'lucide-react';
import { attachVariantImage, deleteVariantImage, uploadVariantImage, type AdminProduct, type ProductImage } from '@/api/products';
import { Button } from '@/components/ui/Button';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { LabelWithHelp } from '@/components/ui/HelpHint';
import { MediaPickerDialog } from '@/features/media/MediaPickerDialog';
import { AltField, ImageLightbox, ProductImageAsset } from '@/features/products/ProductImagesSection';
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

export function VariantImageField({
  product,
  variantId,
  token,
  onProductChange,
}: {
  product: AdminProduct;
  variantId?: number;
  token: string;
  onProductChange: (product: AdminProduct) => void;
}) {
  const inputId = useId();
  const inputRef = useRef<HTMLInputElement>(null);
  const abortRef = useRef<AbortController | null>(null);
  const previewRef = useRef<string | null>(null);
  const productRef = useRef(product);
  productRef.current = product;
  const [progress, setProgress] = useState<number | null>(null);
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);
  const [over, setOver] = useState(false);
  const [confirm, setConfirm] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [lightbox, setLightbox] = useState(false);
  const [picker, setPicker] = useState(false);

  const image = product.variants.find((variant) => variant.id === variantId)?.image ?? null;

  useEffect(() => {
    return () => {
      abortRef.current?.abort();
      if (previewRef.current) {
        URL.revokeObjectURL(previewRef.current);
      }
    };
  }, []);

  function applyImage(next: ProductImage | null) {
    const current = productRef.current;
    onProductChange({
      ...current,
      variants: current.variants.map((variant) =>
        variant.id === variantId ? { ...variant, image: next } : variant
      ),
    });
  }

  function pickFile() {
    inputRef.current?.click();
  }

  async function upload(file: File) {
    if (variantId === undefined) {
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
      const response = await uploadVariantImage(token, product.id, variantId, file, {
        signal: abortRef.current.signal,
        onProgress: setProgress,
      });
      applyImage(response.data.image);
      toast.success(response.message || 'Изображението е записано.');
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
    if (variantId === undefined) {
      return;
    }

    setDeleting(true);
    try {
      const response = await deleteVariantImage(token, product.id, variantId);
      applyImage(null);
      setConfirm(false);
      toast.success(response.message || 'Изображението е изтрито.');
    } catch (error) {
      toastError(error, 'Изтриването не беше успешно.');
    } finally {
      setDeleting(false);
    }
  }

  async function attachFromMedia(mediaId: number) {
    if (variantId === undefined) {
      return;
    }

    try {
      const response = await attachVariantImage(token, product.id, variantId, mediaId);
      applyImage(response.data.image);
      setPicker(false);
      toast.success(response.message || 'Изображението на варианта е записано.');
    } catch (error) {
      toastError(error, 'Изборът от медията не беше успешен.');
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

  if (variantId === undefined) {
    return (
      <p className="m-0 text-base text-muted-foreground">Запишете варианта, за да качите снимка.</p>
    );
  }

  const shown = previewUrl ?? image?.url ?? null;
  const busy = progress !== null;

  return (
    <div>
      <LabelWithHelp
        label="Изображение"
        help="По една снимка на вариант. Качването я записва и в медията. JPEG, PNG или WebP, до 8 MB."
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
            if (image && !busy) {
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
            <ProductImageAsset src={shown} alt={image?.alt || 'Вариант'} className="size-full object-cover" />
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
        <div className="grid min-w-0 flex-1 gap-2">
          {image ? (
            <AltField
              image={image}
              productId={product.id}
              token={token}
              compact
              onSaved={applyImage}
              onError={(text) => toast.error(text)}
            />
          ) : null}
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
          <Button type="button" variant="outline" size="sm" disabled={busy} onClick={() => setPicker(true)}>
            <FolderOpen />
            От медията
          </Button>
            {image && !busy ? (
              <Button type="button" variant="outline" size="sm" onClick={() => setConfirm(true)}>
                <Trash2 />
                Премахни
              </Button>
            ) : null}
          </div>
        </div>
      </div>
      {lightbox && image ? (
        <ImageLightbox images={[image]} index={0} onIndex={() => {}} onClose={() => setLightbox(false)} />
      ) : null}
      {picker ? (
        <MediaPickerDialog
          token={token}
          title="Снимка на варианта"
          onSelect={(files) => {
            const file = files[0];
            if (file) {
              void attachFromMedia(file.id);
            }
          }}
          onClose={() => setPicker(false)}
        />
      ) : null}
      {confirm ? (
        <ConfirmDialog
          title="Премахване на снимка"
          message="Снимката ще се махне от варианта. Ако е в медията, файлът остава там."
          confirmLabel="Изтрий"
          busy={deleting}
          onConfirm={() => void onRemove()}
          onCancel={() => setConfirm(false)}
        />
      ) : null}
    </div>
  );
}
