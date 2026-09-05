import { useState } from 'react';
import { ImageDown } from 'lucide-react';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';

export async function compressImage(file: File): Promise<File> {
  // Animated GIFs must stay untouched: drawing them to canvas would keep only
  // the first frame. All other supported raster images are safely encoded as
  // WebP, including JPEGs that are already too optimized for JPEG quality to
  // make them smaller.
  if (!file.type.startsWith('image/') || file.type === 'image/gif' || file.type === 'image/svg+xml') return file;

  try {
    const bitmap = await createImageBitmap(file);
    const canvas = document.createElement('canvas');
    canvas.width = bitmap.width;
    canvas.height = bitmap.height;
    canvas.getContext('2d')?.drawImage(bitmap, 0, 0);
    bitmap.close();
    // PNG does not support a quality parameter in canvas.toBlob, so it would
    // often be returned unchanged. WebP keeps transparency and gives us a
    // meaningful quality-based reduction for PNG uploads.
    const outputType = 'image/webp';
    const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, outputType, 0.78));
    if (!blob || blob.size >= file.size) return file;
    const outputName = file.type !== 'image/webp'
      ? file.name.replace(/\.[^.]+$/, '.webp')
      : file.name;
    return new File([blob], outputName, { type: blob.type || outputType, lastModified: file.lastModified });
  } catch {
    return file;
  }
}

export async function prepareImageFiles(files: File[], compress: boolean, onPrepared?: (original: File, prepared: File) => void): Promise<File[]> {
  if (!compress) {
    files.forEach((file) => onPrepared?.(file, file));
    return files;
  }
  return Promise.all(files.map(async (file) => {
    const prepared = await compressImage(file);
    onPrepared?.(file, prepared);
    return prepared;
  }));
}

export function useImageCompressionConfirmation() {
  const [pending, setPending] = useState<(() => void) | null>(null);
  const [resolvePending, setResolvePending] = useState<((value: boolean) => void) | null>(null);

  function ask(): Promise<boolean> {
    return new Promise((resolve) => {
      setResolvePending(() => resolve);
      setPending(() => () => resolve(true));
    });
  }

  function finish(value: boolean) {
    resolvePending?.(value);
    setResolvePending(null);
    setPending(null);
  }

  const dialog = pending ? (
    <ConfirmDialog
      title="Компресиране на изображения"
      message="Да бъдат ли компресирани избраните изображения преди качване?"
      description="Компресията намалява размера на файловете и ускорява зареждането. Качеството се запазва оптимално."
      confirmLabel="Да, компресирай"
      confirmIcon={ImageDown}
      variant="default"
      onConfirm={() => finish(true)}
      onCancel={() => finish(false)}
    />
  ) : null;

  return { ask, dialog };
}
