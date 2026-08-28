export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

export class ApiError extends Error {
  constructor(
    message: string,
    public readonly status: number,
    public readonly payload: unknown = null
  ) {
    super(message);
    this.name = 'ApiError';
  }

  fieldErrors(): Record<string, string> {
    if (this.payload === null || typeof this.payload !== 'object' || !('errors' in this.payload)) {
      return {};
    }

    const raw = this.payload.errors;

    if (raw === null || typeof raw !== 'object') {
      return {};
    }

    const mapped: Record<string, string> = {};

    for (const [field, messages] of Object.entries(raw)) {
      if (Array.isArray(messages) && typeof messages[0] === 'string') {
        mapped[field] = messages[0];
      }
    }

    return mapped;
  }
}

export type ApiEnvelope<T> = {
  success: boolean;
  message: string;
  data: T;
};

type RequestOptions = {
  method?: HttpMethod;
  body?: unknown;
  token?: string | null;
  query?: Record<string, string | number | boolean | undefined>;
};

export async function apiRequest<T>(path: string, options: RequestOptions = {}): Promise<ApiEnvelope<T>> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
  };

  if (options.body !== undefined) {
    headers['Content-Type'] = 'application/json';
  }

  if (options.token) {
    headers.Authorization = `Bearer ${options.token}`;
  }

  const url = new URL(path, window.location.origin);

  if (options.query) {
    for (const [key, value] of Object.entries(options.query)) {
      if (value !== undefined && value !== '') {
        url.searchParams.set(key, String(value));
      }
    }
  }

  const response = await fetch(`${url.pathname}${url.search}`, {
    method: options.method ?? 'GET',
    headers,
    body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
  });

  const data: unknown = await response.json().catch(() => null);

  return readEnvelope<T>(response.status, data);
}

type UploadOptions = {
  method?: 'POST' | 'PUT' | 'PATCH';
  form: FormData;
  token?: string | null;
  signal?: AbortSignal;
  onProgress?: (percent: number) => void;
};

export function apiUpload<T>(path: string, options: UploadOptions): Promise<ApiEnvelope<T>> {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open(options.method ?? 'POST', path);
    xhr.responseType = 'json';
    xhr.setRequestHeader('Accept', 'application/json');

    if (options.token) {
      xhr.setRequestHeader('Authorization', `Bearer ${options.token}`);
    }

    xhr.upload.onprogress = (event) => {
      if (event.lengthComputable) {
        options.onProgress?.(Math.round((event.loaded / event.total) * 100));
      }
    };

    xhr.onload = () => {
      try {
        resolve(readEnvelope<T>(xhr.status, xhrBody(xhr)));
      } catch (error) {
        reject(error);
      }
    };

    xhr.onerror = () => {
      reject(new ApiError('Заявката не беше успешна.', 0));
    };

    xhr.onabort = () => {
      reject(new DOMException('Качването е отказано.', 'AbortError'));
    };

    const abort = () => xhr.abort();
    options.signal?.addEventListener('abort', abort, { once: true });
    xhr.send(options.form);
  });
}

function xhrBody(xhr: XMLHttpRequest): unknown {
  const raw = xhr.response ?? xhr.responseText;

  if (raw === null || raw === '') {
    return null;
  }

  if (typeof raw === 'string') {
    try {
      return JSON.parse(raw);
    } catch {
      return null;
    }
  }

  return raw;
}

function readEnvelope<T>(status: number, data: unknown): ApiEnvelope<T> {
  if (status < 200 || status >= 300) {
    const message =
      data !== null && typeof data === 'object' && 'message' in data && typeof data.message === 'string'
        ? data.message
        : 'Заявката не беше успешна.';

    throw new ApiError(message, status, data);
  }

  return data as ApiEnvelope<T>;
}
