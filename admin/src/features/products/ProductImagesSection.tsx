import { useEffect, useId, useRef, useState, type DragEvent } from 'react';
import {
  ChevronLeft,
  ChevronRight,
  FolderOpen,
  ImagePlus,
  Images,
  LoaderCircle,
  Star,
  TextCursorInput,
  Trash2,
  Upload,
  X,
  ZoomIn,
} from 'lucide-react';
import { ApiError } from '@/api/client';
import type { MediaFile } from '@/api/media';
import {
  attachProductFrontImage,
  attachProductGalleryImages,
  deleteProductImage,
  getProduct,
  makeProductImageFront,
  updateProductImage,
  uploadProductFrontImage,
  uploadProductGalleryImage,
  type AdminProduct,
  type ProductImage,
} from '@/api/products';
import { Button } from '@/components/ui/Button';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { LabelWithHelp } from '@/components/ui/HelpHint';
import { MediaPickerDialog } from '@/features/media/MediaPickerDialog';
import { formatBytes } from '@/lib/format';
import { toast, toastError } from '@/lib/toast';
import { cn } from '@/lib/utils';

const ACCEPT = 'image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp';
const MAX_BYTES = 8 * 1024 * 1024;
const DRAG_IMAGE_ID = 'application/x-borz-image-id';
const ALLOWED_TYPES = new Set(['image/jpeg', 'image/jpg', 'image/png', 'image/webp']);

type PendingUpload = {
  key: string;
  name: string;
  previewUrl: string;
  progress: number;
  error: string | null;
  target: 'front' | 'gallery';
};

type Job = {
  key: string;
  file: File;
  target: 'front' | 'gallery';
};

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

function takeFiles(list: FileList | File[] | null | undefined): File[] {
  return list ? Array.from(list) : [];
}

function allImages(product: AdminProduct): ProductImage[] {
  return product.front_image ? [product.front_image, ...product.gallery_images] : [...product.gallery_images];
}

function isAbortError(error: unknown): boolean {
  return error instanceof DOMException && error.name === 'AbortError';
}

export function ProductImagesEditor({
  product,
  token,
  onProductChange,
}: {
  product: AdminProduct;
  token: string;
  onProductChange: (product: AdminProduct) => void;
}) {
  const frontInputId = useId();
  const galleryInputId = useId();
  const frontInputRef = useRef<HTMLInputElement>(null);
  const galleryInputRef = useRef<HTMLInputElement>(null);
  const productRef = useRef(product);
  const pendingRef = useRef<PendingUpload[]>([]);
  const queueRef = useRef<Job[]>([]);
  const drainingRef = useRef(false);
  const frontAbortRef = useRef<AbortController | null>(null);
  const enqueueRef = useRef<(files: File[], target: 'front' | 'gallery') => void>(() => {});
  productRef.current = product;

  const [pending, setPending] = useState<PendingUpload[]>([]);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [dropTarget, setDropTarget] = useState<'front' | 'gallery' | null>(null);
  const [draggingId, setDraggingId] = useState<number | null>(null);
  const [overId, setOverId] = useState<number | null>(null);
  const [confirm, setConfirm] = useState<ProductImage | null>(null);
  const [deleting, setDeleting] = useState(false);
  const [lightbox, setLightbox] = useState<number | null>(null);
  const [hovered, setHovered] = useState(false);
  const [picker, setPicker] = useState<'front' | 'gallery' | null>(null);

  const pendingFront = pending.find((item) => item.target === 'front');
  const pendingGallery = pending.filter((item) => item.target === 'gallery');

  useEffect(() => {
    return () => {
      frontAbortRef.current?.abort();
      pendingRef.current.forEach((item) => URL.revokeObjectURL(item.previewUrl));
    };
  }, []);

  async function refresh() {
    const response = await getProduct(token, product.id);
    onProductChange(response.data.product);
  }

  async function attachFromMedia(picked: MediaFile[]) {
    if (picked.length === 0 || !picker) {
      return;
    }

    try {
      if (picker === 'front') {
        const response = await attachProductFrontImage(token, product.id, picked[0].id);
        toast.success(response.message || 'Предното изображение е записано.');
      } else {
        const response = await attachProductGalleryImages(
          token,
          product.id,
          picked.map((file) => file.id)
        );
        toast.success(response.message || 'Изображенията са добавени.');
      }
      await refresh();
      setPicker(null);
    } catch (error) {
      toastError(error, 'Изборът от медията не беше успешен.');
    }
  }

  function enqueue(files: File[], target: 'front' | 'gallery') {
    const accepted: File[] = [];
    const errors: string[] = [];

    for (const file of files) {
      const reason = validateFile(file);
      if (reason) {
        errors.push(reason);
      } else {
        accepted.push(file);
      }
    }

    if (errors.length > 0) {
      toast.error(errors.join(' '));
    }

    if (accepted.length === 0) {
      return;
    }

    let frontFile: File | null = null;
    let galleryFiles = accepted;

    if (target === 'front') {
      frontFile = accepted[0] ?? null;
      galleryFiles = accepted.slice(1);
    }

    const jobs: Job[] = [];
    const nextPending: PendingUpload[] = [];

    if (frontFile) {
      frontAbortRef.current?.abort();
      frontAbortRef.current = new AbortController();
      queueRef.current = queueRef.current.filter((job) => job.target !== 'front');
      const key = `front-${Date.now()}-${frontFile.name}`;
      jobs.push({ key, file: frontFile, target: 'front' });
      nextPending.push({
        key,
        name: frontFile.name,
        previewUrl: URL.createObjectURL(frontFile),
        progress: 0,
        error: null,
        target: 'front',
      });
    }

    for (const file of galleryFiles) {
      const key = `gallery-${Date.now()}-${file.name}-${Math.random().toString(16).slice(2)}`;
      jobs.push({ key, file, target: 'gallery' });
      nextPending.push({
        key,
        name: file.name,
        previewUrl: URL.createObjectURL(file),
        progress: 0,
        error: null,
        target: 'gallery',
      });
    }

    setPending((current) => {
      const kept = frontFile
        ? current.filter((item) => {
            if (item.target !== 'front') {
              return true;
            }
            URL.revokeObjectURL(item.previewUrl);
            return false;
          })
        : current;
      const next = [...kept, ...nextPending];
      pendingRef.current = next;
      return next;
    });
    queueRef.current.push(...jobs);
    void drain();
  }

  enqueueRef.current = enqueue;

  useEffect(() => {
    function onWindowPaste(event: globalThis.ClipboardEvent) {
      if (!hovered) {
        return;
      }

      const files = takeFiles(event.clipboardData?.files).filter((file) => file.type.startsWith('image/'));

      if (files.length === 0) {
        return;
      }

      event.preventDefault();
      enqueueRef.current(
        files,
        productRef.current.front_image || pendingRef.current.some((item) => item.target === 'front')
          ? 'gallery'
          : 'front'
      );
    }

    window.addEventListener('paste', onWindowPaste);
    return () => window.removeEventListener('paste', onWindowPaste);
  }, [hovered]);

  async function drain() {
    if (drainingRef.current) {
      return;
    }

    drainingRef.current = true;

    while (queueRef.current.length > 0) {
      const job = queueRef.current.shift();
      if (!job) {
        break;
      }

      await runJob(job);
    }

    drainingRef.current = false;

    try {
      await refresh();
    } catch {
      // local state already updated
    }
  }

  function patchPending(key: string, patch: Partial<PendingUpload>) {
    setPending((current) => {
      const next = current.map((item) => (item.key === key ? { ...item, ...patch } : item));
      pendingRef.current = next;
      return next;
    });
  }

  function removePending(key: string) {
    setPending((current) => {
      const match = current.find((item) => item.key === key);
      if (match) {
        URL.revokeObjectURL(match.previewUrl);
      }
      const next = current.filter((item) => item.key !== key);
      pendingRef.current = next;
      return next;
    });
  }

  async function runJob(job: Job) {
    try {
      if (job.target === 'front') {
        const response = await uploadProductFrontImage(token, product.id, job.file, {
          signal: frontAbortRef.current?.signal,
          onProgress: (percent) => patchPending(job.key, { progress: percent }),
        });
        const current = productRef.current;
        onProductChange({ ...current, front_image: response.data.image });
        toast.success(response.message);
      } else {
        const response = await uploadProductGalleryImage(token, product.id, job.file, {
          onProgress: (percent) => patchPending(job.key, { progress: percent }),
        });
        const current = productRef.current;
        onProductChange({
          ...current,
          gallery_images: [...current.gallery_images, ...response.data.images],
        });
        toast.success(response.message);
      }

      removePending(job.key);
    } catch (error) {
      if (isAbortError(error)) {
        removePending(job.key);
        return;
      }

      patchPending(job.key, {
        error: error instanceof ApiError ? error.message : 'Качването не беше успешно.',
        progress: 0,
      });
      toastError(error, 'Качването не беше успешно.');
    }
  }

  async function onMakeFront(image: ProductImage) {
    setBusyId(image.id);

    try {
      await makeProductImageFront(token, product.id, image.id);
      await refresh();
      toast.success('Изображението е зададено като предно.');
    } catch (error) {
      toastError(error, 'Не можа да се зададе като предно.');
    } finally {
      setBusyId(null);
    }
  }

  async function onDelete(image: ProductImage) {
    setDeleting(true);

    try {
      await deleteProductImage(token, product.id, image.id);
      setConfirm(null);
      await refresh();
      toast.success('Изображението е изтрито.');
    } catch (error) {
      toastError(error, 'Изображението не можа да се изтрие.');
    } finally {
      setDeleting(false);
      setBusyId(null);
    }
  }

  async function persistOrder(next: ProductImage[]) {
    const current = productRef.current;
    onProductChange({ ...current, gallery_images: next });

    try {
      await Promise.all(
        next.map((image, index) =>
          updateProductImage(token, product.id, image.id, { sort_order: index + 1 })
        )
      );
    } catch (error) {
      toastError(error, 'Редът не можа да се запише.');
      await refresh();
    }
  }

  function reorderGallery(fromId: number, toId: number) {
    if (fromId === toId) {
      return;
    }

    const items = [...product.gallery_images];
    const from = items.findIndex((image) => image.id === fromId);
    const to = items.findIndex((image) => image.id === toId);

    if (from < 0 || to < 0) {
      return;
    }

    const [moved] = items.splice(from, 1);
    items.splice(to, 0, moved);
    void persistOrder(items);
  }

  function moveGallery(imageId: number, direction: -1 | 1) {
    const items = [...product.gallery_images];
    const index = items.findIndex((image) => image.id === imageId);
    const nextIndex = index + direction;

    if (index < 0 || nextIndex < 0 || nextIndex >= items.length) {
      return;
    }

    const [moved] = items.splice(index, 1);
    items.splice(nextIndex, 0, moved);
    void persistOrder(items);
  }

  function onDropFront(event: DragEvent) {
    event.preventDefault();
    setDropTarget(null);
    const imageId = Number(event.dataTransfer.getData(DRAG_IMAGE_ID));
    const files = takeFiles(event.dataTransfer.files);

    if (files.length > 0) {
      enqueue(files, 'front');
      return;
    }

    if (Number.isInteger(imageId) && imageId > 0) {
      const image = product.gallery_images.find((item) => item.id === imageId);
      if (image) {
        void onMakeFront(image);
      }
    }
  }

  function onDropGallery(event: DragEvent) {
    event.preventDefault();
    setDropTarget(null);
    setOverId(null);
    const files = takeFiles(event.dataTransfer.files);
    const imageId = Number(event.dataTransfer.getData(DRAG_IMAGE_ID));

    if (files.length > 0) {
      enqueue(files, 'gallery');
      return;
    }

    if (Number.isInteger(imageId) && imageId > 0 && overId) {
      reorderGallery(imageId, overId);
    }
  }

  const viewerImages = allImages(product);

  return (
    <div className="grid gap-5" onMouseEnter={() => setHovered(true)} onMouseLeave={() => setHovered(false)}>
      <input
        ref={frontInputRef}
        id={frontInputId}
        type="file"
        accept={ACCEPT}
        className="sr-only"
        onChange={(event) => {
          enqueue(takeFiles(event.target.files), 'front');
          event.target.value = '';
        }}
      />
      <input
        ref={galleryInputRef}
        id={galleryInputId}
        type="file"
        accept={ACCEPT}
        multiple
        className="sr-only"
        onChange={(event) => {
          enqueue(takeFiles(event.target.files), 'gallery');
          event.target.value = '';
        }}
      />

      <div>
        <LabelWithHelp
          label="Предно изображение"
          help="Показва се в списъка и като основна снимка. Качването записва файла и в медията. JPEG, PNG или WebP, до 8 MB."
        />
        <div
          className={cn(
            'mt-2 overflow-hidden rounded-[6px] border border-dashed border-border bg-field transition-colors',
            dropTarget === 'front' && 'border-primary bg-primary/6'
          )}
          onDragOver={(event) => {
            event.preventDefault();
            setDropTarget('front');
          }}
          onDragLeave={() => setDropTarget((current) => (current === 'front' ? null : current))}
          onDrop={onDropFront}
        >
          {product.front_image || pendingFront ? (
            <div className="grid gap-3 p-3 sm:grid-cols-[minmax(0,280px)_minmax(0,1fr)] sm:items-start">
              <ImageTile
                src={pendingFront?.previewUrl ?? product.front_image?.url ?? ''}
                alt={product.front_image?.alt || product.name}
                pending={pendingFront}
                badge="Предно"
                onOpen={product.front_image ? () => setLightbox(0) : undefined}
              />
              <div className="grid min-w-0 gap-3">
                <p className="m-0 text-base text-muted-foreground">
                  {pendingFront
                    ? `Качване на ${pendingFront.name}…`
                    : `${product.front_image?.original_name ?? ''} · ${formatBytes(product.front_image?.size ?? 0)}`}
                </p>
                {product.front_image ? (
                  <AltField
                    image={product.front_image}
                    productId={product.id}
                    token={token}
                    disabled={busyId === product.front_image.id}
                    onSaved={(image) =>
                      onProductChange({ ...productRef.current, front_image: image })
                    }
                    onError={(text) => toast.error(text)}
                  />
                ) : null}
                <div className="flex flex-wrap gap-2">
                  <Button type="button" variant="outline" onClick={() => frontInputRef.current?.click()}>
                    <Upload />
                    Смени
                  </Button>
                  <Button type="button" variant="outline" onClick={() => setPicker('front')}>
                    <FolderOpen />
                    От медията
                  </Button>
                  {product.front_image ? (
                    <Button
                      type="button"
                      variant="outline"
                      disabled={busyId === product.front_image.id}
                      onClick={() => setConfirm(product.front_image)}
                    >
                      <Trash2 />
                      Премахни
                    </Button>
                  ) : null}
                </div>
              </div>
            </div>
          ) : (
            <DropEmpty
              title="Пуснете предното изображение"
              hint="или качете файл. Качването го записва и в медията. Може и Ctrl+V."
              onPick={() => frontInputRef.current?.click()}
              onPickMedia={() => setPicker('front')}
            />
          )}
        </div>
      </div>

      <div>
        <LabelWithHelp
          label="Допълнителни изображения"
          help="Галерия към продукта. Новите качвания отиват и в медията. Плъзгайте снимките, за да ги пренаредите."
        />
        <div
          className={cn(
            'mt-2 rounded-[6px] border border-dashed border-border bg-field p-3 transition-colors',
            dropTarget === 'gallery' && 'border-primary bg-primary/6'
          )}
          onDragOver={(event) => {
            event.preventDefault();
            setDropTarget('gallery');
          }}
          onDragLeave={() => setDropTarget((current) => (current === 'gallery' ? null : current))}
          onDrop={onDropGallery}
        >
          {product.gallery_images.length === 0 && pendingGallery.length === 0 ? (
            <DropEmpty
              title="Пуснете допълнителни снимки"
              hint="Може да изберете няколко файла или да вземете готови от медията."
              onPick={() => galleryInputRef.current?.click()}
              onPickMedia={() => setPicker('gallery')}
            />
          ) : (
            <ul className="m-0 grid w-full list-none grid-cols-2 items-start gap-2 p-0 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
              {product.gallery_images.map((image, index) => (
                <li
                  key={image.id}
                  className={cn(overId === image.id && draggingId !== image.id && 'opacity-60')}
                  onDragOver={(event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    setOverId(image.id);
                  }}
                  onDrop={(event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    setDropTarget(null);
                    setOverId(null);
                    const files = takeFiles(event.dataTransfer.files);
                    if (files.length > 0) {
                      enqueue(files, 'gallery');
                      return;
                    }
                    const fromId = Number(event.dataTransfer.getData(DRAG_IMAGE_ID));
                    if (Number.isInteger(fromId) && fromId > 0) {
                      reorderGallery(fromId, image.id);
                    }
                  }}
                >
                  <GalleryCard
                    image={image}
                    productName={product.name}
                    busy={busyId === image.id}
                    draggable
                    onDragStart={() => setDraggingId(image.id)}
                    onDragEnd={() => {
                      setDraggingId(null);
                      setOverId(null);
                    }}
                    canMoveLeft={index > 0}
                    canMoveRight={index < product.gallery_images.length - 1}
                    onMoveLeft={() => moveGallery(image.id, -1)}
                    onMoveRight={() => moveGallery(image.id, 1)}
                    onOpen={() => setLightbox(product.front_image ? index + 1 : index)}
                    onMakeFront={() => void onMakeFront(image)}
                    onDelete={() => setConfirm(image)}
                    onAltSaved={(saved) =>
                      onProductChange({
                        ...productRef.current,
                        gallery_images: productRef.current.gallery_images.map((item) =>
                          item.id === saved.id ? saved : item
                        ),
                      })
                    }
                    productId={product.id}
                    token={token}
                    onAltError={(text) => toast.error(text)}
                  />
                </li>
              ))}
              {pendingGallery.map((item) => (
                <li key={item.key}>
                  <ImageTile src={item.previewUrl} alt={item.name} pending={item} />
                </li>
              ))}
              <li className="grid gap-1.5">
                <button
                  type="button"
                  className="flex aspect-square w-full cursor-pointer flex-col items-center justify-center gap-1.5 rounded-[6px] border border-dashed border-border bg-field px-2 py-3 text-center text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:bg-muted focus-visible:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                  onClick={() => galleryInputRef.current?.click()}
                >
                  <ImagePlus className="size-5" aria-hidden />
                  Добави снимки
                </button>
                <Button type="button" variant="outline" size="sm" onClick={() => setPicker('gallery')}>
                  <FolderOpen />
                  От медията
                </Button>
              </li>
            </ul>
          )}
        </div>
      </div>

      {confirm ? (
        <ConfirmDialog
          title="Изтриване на изображение"
          message={`„${confirm.original_name}“ ще се махне от продукта. Ако е в медията, файлът остава там.`}
          confirmLabel="Изтрий"
          busy={deleting}
          onCancel={() => setConfirm(null)}
          onConfirm={() => void onDelete(confirm)}
        />
      ) : null}

      {picker ? (
        <MediaPickerDialog
          token={token}
          title={picker === 'front' ? 'Предно изображение от медията' : 'Снимки от медията'}
          multiple={picker === 'gallery'}
          onSelect={(files) => void attachFromMedia(files)}
          onClose={() => setPicker(null)}
        />
      ) : null}

      {lightbox !== null && viewerImages[lightbox] ? (
        <ImageLightbox
          images={viewerImages}
          index={lightbox}
          onIndex={setLightbox}
          onClose={() => setLightbox(null)}
        />
      ) : null}
    </div>
  );
}

export function ProductImagesPreview({ product }: { product: AdminProduct }) {
  const [lightbox, setLightbox] = useState<number | null>(null);
  const images = allImages(product);

  if (images.length === 0) {
    return <p className="mb-0 text-base text-muted-foreground">Няма качени изображения.</p>;
  }

  return (
    <div className="grid gap-4">
      {product.front_image ? (
        <ImagePreview
          image={product.front_image}
          className="max-w-sm"
          onOpen={() => setLightbox(0)}
        />
      ) : null}
      {product.gallery_images.length > 0 ? (
        <ul className="m-0 grid list-none grid-cols-2 gap-3 p-0 sm:grid-cols-4">
          {product.gallery_images.map((image, index) => (
            <li key={image.id}>
              <ImagePreview
                image={image}
                onOpen={() => setLightbox(product.front_image ? index + 1 : index)}
              />
            </li>
          ))}
        </ul>
      ) : null}
      {lightbox !== null && images[lightbox] ? (
        <ImageLightbox images={images} index={lightbox} onIndex={setLightbox} onClose={() => setLightbox(null)} />
      ) : null}
    </div>
  );
}

function imageAltText(image: ProductImage): string {
  return image.alt?.trim() ?? '';
}

export function ProductImageAsset({
  src,
  alt,
  className,
  loading = 'eager',
}: {
  src: string;
  alt: string;
  className: string;
  loading?: 'eager' | 'lazy';
}) {
  const imageRef = useRef<HTMLImageElement>(null);
  const [loaded, setLoaded] = useState(false);

  useEffect(() => {
    setLoaded(Boolean(imageRef.current?.complete));
  }, [src]);

  return (
    <span className="relative block overflow-hidden bg-muted" aria-busy={!loaded}>
      {!loaded ? (
        <span className="absolute inset-0 z-10 grid place-items-center bg-muted text-muted-foreground">
          <LoaderCircle className="size-5 animate-spin" aria-hidden />
          <span className="sr-only">Зареждане на изображение</span>
        </span>
      ) : null}
      <img
        ref={imageRef}
        src={src}
        alt={alt}
        className={cn('block transition-opacity duration-150', loaded ? 'opacity-100' : 'opacity-0', className)}
        loading={loading}
        decoding="async"
        onLoad={() => setLoaded(true)}
        onError={() => setLoaded(true)}
      />
    </span>
  );
}

function ImagePreview({
  image,
  onOpen,
  className,
}: {
  image: ProductImage;
  onOpen: () => void;
  className?: string;
}) {
  const alt = imageAltText(image);

  return (
    <figure className={cn('m-0', className)}>
      <button
        type="button"
        className="w-full cursor-zoom-in overflow-hidden rounded-[6px] border border-border bg-muted p-0 outline-none transition-opacity hover:opacity-90 focus-visible:ring-[3px] focus-visible:ring-ring/50"
        onClick={onOpen}
      >
        <ProductImageAsset src={image.url} alt={alt} className="aspect-square size-full object-cover" />
      </button>
      {alt ? <figcaption className="mt-2 text-base text-muted-foreground">{alt}</figcaption> : null}
    </figure>
  );
}

function DropEmpty({
  title,
  hint,
  onPick,
  onPickMedia,
}: {
  title: string;
  hint: string;
  onPick: () => void;
  onPickMedia?: () => void;
}) {
  return (
    <div className="flex min-h-44 w-full flex-col items-center justify-center gap-3 px-4 py-8 text-center">
      <button
        type="button"
        className="flex w-full cursor-pointer flex-col items-center justify-center gap-2 outline-none transition-colors hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
        onClick={onPick}
      >
        <span className="flex size-12 items-center justify-center rounded-[6px] bg-primary/12 text-primary">
          <Images className="size-6" aria-hidden />
        </span>
        <span className="font-sans text-base font-bold text-foreground">{title}</span>
        <span className="max-w-md text-base text-muted-foreground">{hint}</span>
      </button>
      {onPickMedia ? (
        <Button type="button" variant="outline" onClick={onPickMedia}>
          <FolderOpen />
          От медията
        </Button>
      ) : null}
    </div>
  );
}

function ImageTile({
  src,
  alt,
  pending,
  badge,
  onOpen,
}: {
  src: string;
  alt: string;
  pending?: PendingUpload;
  badge?: string;
  onOpen?: () => void;
}) {
  const body = (
    <>
      <ProductImageAsset src={src} alt={alt} className="aspect-square size-full object-cover" />
      {badge ? (
        <span className="pointer-events-none absolute top-2 left-2 inline-flex items-center rounded-[6px] bg-[#173f32] px-3 py-1.5 text-base font-extrabold tracking-wide text-[#f3efe6] shadow-lg">
          {badge}
        </span>
      ) : null}
      {pending ? (
        <div className="absolute inset-0 flex flex-col justify-end bg-foreground/40 p-2">
          <p className="m-0 truncate text-xs font-bold text-background">{pending.error ?? pending.name}</p>
          <div className="mt-2 h-1 overflow-hidden rounded-full bg-background/40">
            <div
              className="h-full bg-background transition-[width] duration-150"
              style={{ width: `${pending.error ? 0 : pending.progress}%` }}
            />
          </div>
        </div>
      ) : null}
    </>
  );

  if (onOpen) {
    return (
      <button
        type="button"
        className="relative block w-full overflow-hidden rounded-[6px] border border-border bg-muted p-0 outline-none transition-opacity hover:opacity-90 focus-visible:ring-[3px] focus-visible:ring-ring/50"
        onClick={onOpen}
      >
        {body}
      </button>
    );
  }

  return <div className="relative overflow-hidden rounded-[6px] border border-border bg-muted">{body}</div>;
}

function GalleryCard({
  image,
  productName,
  productId,
  token,
  busy,
  draggable,
  canMoveLeft,
  canMoveRight,
  onDragStart,
  onDragEnd,
  onMoveLeft,
  onMoveRight,
  onOpen,
  onMakeFront,
  onDelete,
  onAltSaved,
  onAltError,
}: {
  image: ProductImage;
  productName: string;
  productId: number;
  token: string;
  busy: boolean;
  draggable: boolean;
  canMoveLeft: boolean;
  canMoveRight: boolean;
  onDragStart: () => void;
  onDragEnd: () => void;
  onMoveLeft: () => void;
  onMoveRight: () => void;
  onOpen: () => void;
  onMakeFront: () => void;
  onDelete: () => void;
  onAltSaved: (image: ProductImage) => void;
  onAltError: (message: string) => void;
}) {
  const [altOpen, setAltOpen] = useState(false);

  return (
    <>
      <div
        className="overflow-hidden rounded-[6px] border border-border bg-card"
        draggable={draggable && !busy}
        onDragStart={(event) => {
          event.dataTransfer.setData(DRAG_IMAGE_ID, String(image.id));
          event.dataTransfer.effectAllowed = 'move';
          onDragStart();
        }}
        onDragEnd={onDragEnd}
      >
        <div className="relative">
          <ProductImageAsset src={image.url} alt={image.alt || productName} className="aspect-square size-full object-cover" />
          <div className="absolute inset-x-0 bottom-0 flex justify-between gap-1 bg-gradient-to-t from-foreground/70 to-transparent p-1.5">
            <Button type="button" size="icon" variant="secondary" className="size-8" aria-label="Преглед" onClick={onOpen}>
              <ZoomIn />
            </Button>
            <Button
              type="button"
              size="icon"
              variant="secondary"
              className="size-8"
              aria-label="Направи предно"
              disabled={busy}
              onClick={onMakeFront}
            >
              <Star />
            </Button>
          </div>
        </div>
        <div className="flex justify-center gap-0.5 p-1.5">
          <Button type="button" size="icon" variant="outline" className="size-7" aria-label="Алтернативен текст" disabled={busy} onClick={() => setAltOpen(true)}>
            <TextCursorInput />
          </Button>
          <Button
            type="button"
            size="icon"
            variant="outline"
            className="size-7"
            aria-label="Наляво"
            disabled={!canMoveLeft || busy}
            onClick={onMoveLeft}
          >
            <ChevronLeft />
          </Button>
          <Button
            type="button"
            size="icon"
            variant="outline"
            className="size-7"
            aria-label="Надясно"
            disabled={!canMoveRight || busy}
            onClick={onMoveRight}
          >
            <ChevronRight />
          </Button>
          <Button
            type="button"
            size="icon"
            variant="outline"
            className="size-7"
            aria-label="Изтрий"
            disabled={busy}
            onClick={onDelete}
          >
            <Trash2 />
          </Button>
        </div>
      </div>
      {altOpen ? (
        <div className="dialog-root">
          <button type="button" className="dialog-backdrop" aria-label="Затвори" onClick={() => setAltOpen(false)} />
          <div className="dialog" role="dialog" aria-modal="true" aria-labelledby={`gallery-alt-title-${image.id}`}>
            <div className="mb-3 flex items-start justify-between gap-2">
              <h2 id={`gallery-alt-title-${image.id}`}>Алтернативен текст</h2>
              <Button type="button" size="icon" variant="outline" aria-label="Затвори" onClick={() => setAltOpen(false)}><X /></Button>
            </div>
            <AltField image={image} productId={productId} token={token} disabled={busy} onSaved={onAltSaved} onError={onAltError} />
            <div className="mt-4 flex justify-end">
              <Button type="button" onClick={() => setAltOpen(false)}>Готово</Button>
            </div>
          </div>
        </div>
      ) : null}
    </>
  );
}

export function AltField({
  image,
  productId,
  token,
  disabled,
  compact,
  onSaved,
  onError,
}: {
  image: ProductImage;
  productId: number;
  token: string;
  disabled?: boolean;
  compact?: boolean;
  onSaved: (image: ProductImage) => void;
  onError: (message: string) => void;
}) {
  const [value, setValue] = useState(image.alt ?? '');
  const [saving, setSaving] = useState(false);
  const lastSaved = useRef(image.alt ?? '');

  useEffect(() => {
    setValue(image.alt ?? '');
    lastSaved.current = image.alt ?? '';
  }, [image.id, image.alt]);

  async function save() {
    const next = value.trim();
    if (next === lastSaved.current || disabled) {
      return;
    }

    setSaving(true);

    try {
      const response = await updateProductImage(token, productId, image.id, { alt: next === '' ? null : next });
      lastSaved.current = response.data.image.alt ?? '';
      setValue(lastSaved.current);
      onSaved(response.data.image);
    } catch (error) {
      onError(error instanceof ApiError ? error.message : 'Алтернативният текст не можа да се запише.');
    } finally {
      setSaving(false);
    }
  }

  return (
    <label className="grid gap-1">
      {compact ? null : <span className="text-base font-bold">Алтернативен текст</span>}
      <input
        value={value}
        disabled={disabled || saving}
        placeholder={compact ? 'Алтернативен текст' : 'Описание за достъпност'}
        className="min-h-12 w-full rounded-[6px] border border-input bg-field px-2.5 text-base outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
        onChange={(event) => setValue(event.target.value)}
        onBlur={() => void save()}
        onKeyDown={(event) => {
          if (event.key === 'Enter') {
            event.preventDefault();
            void save();
          }
        }}
      />
    </label>
  );
}

export function ImageLightbox({
  images,
  index,
  onIndex,
  onClose,
}: {
  images: ProductImage[];
  index: number;
  onIndex: (index: number) => void;
  onClose: () => void;
}) {
  const image = images[index];

  useEffect(() => {
    function onKey(event: globalThis.KeyboardEvent) {
      if (event.key === 'Escape') {
        onClose();
      }
      if (event.key === 'ArrowLeft') {
        onIndex((index - 1 + images.length) % images.length);
      }
      if (event.key === 'ArrowRight') {
        onIndex((index + 1) % images.length);
      }
    }

    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [index, images.length, onClose, onIndex]);

  if (!image) {
    return null;
  }

  return (
    <div className="dialog-root">
      <button type="button" className="dialog-backdrop" aria-label="Затвори" onClick={onClose} />
      <div
        className="fixed inset-4 z-50 m-auto flex max-h-[calc(100%-2rem)] max-w-5xl flex-col gap-3 rounded-[12px] border border-border bg-card p-3 shadow-lg"
        role="dialog"
        aria-modal="true"
        aria-label="Преглед на изображение"
      >
        <div className="flex items-center justify-between gap-2">
          <p className="m-0 truncate text-base font-bold">
            {image.original_name}
            {image.role === 'front' ? ' · Предно' : ''}
          </p>
          <Button type="button" size="icon" variant="outline" aria-label="Затвори" onClick={onClose}>
            <X />
          </Button>
        </div>
        <div className="relative min-h-0 flex-1 overflow-hidden rounded-[6px] bg-muted">
          <img src={image.url} alt={image.alt || image.original_name} className="mx-auto max-h-[70vh] w-auto object-contain" />
        </div>
        {images.length > 1 ? (
          <div className="flex items-center justify-between gap-2">
            <Button type="button" variant="outline" onClick={() => onIndex((index - 1 + images.length) % images.length)}>
              <ChevronLeft />
              Предишна
            </Button>
            <span className="text-base text-muted-foreground">
              {index + 1} / {images.length}
            </span>
            <Button type="button" variant="outline" onClick={() => onIndex((index + 1) % images.length)}>
              <ChevronRight />
              Следваща
            </Button>
          </div>
        ) : null}
      </div>
    </div>
  );
}
