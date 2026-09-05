export const MEDIA_MAX_BYTES = 128 * 1024 * 1024;

export const BLOCKED_EXTENSIONS = new Set([
  'php',
  'php3',
  'php4',
  'php5',
  'phtml',
  'phar',
  'cgi',
  'fcgi',
  'exe',
  'bat',
  'cmd',
  'com',
  'scr',
  'pif',
  'msi',
  'htaccess',
  'htpasswd',
  'html',
  'htm',
  'xhtml',
  'js',
  'mjs',
  'svg',
]);

export function mediaKindLabel(kind: string): string {
  if (kind === 'image') {
    return 'Изображение';
  }

  if (kind === 'video') {
    return 'Видео';
  }

  if (kind === 'audio') {
    return 'Аудио';
  }

  if (kind === 'document') {
    return 'Документ';
  }

  return 'Друго';
}

export function fileExtension(name: string): string {
  const parts = name.split('.');

  if (parts.length < 2) {
    return '';
  }

  return parts[parts.length - 1]?.toLowerCase().replace(/[^a-z0-9]+/g, '') ?? '';
}

export function validateMediaFile(file: File): string | null {
  const extension = fileExtension(file.name);

  if (extension && BLOCKED_EXTENSIONS.has(extension)) {
    return `${file.name}: този тип файл не е разрешен.`;
  }

  if (file.size > MEDIA_MAX_BYTES) {
    return `${file.name}: най-много 128 MB.`;
  }

  return null;
}
