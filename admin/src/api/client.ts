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
}

type RequestOptions = {
  method?: HttpMethod;
  body?: unknown;
  token?: string | null;
};

export async function apiRequest<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
  };

  if (options.body !== undefined) {
    headers['Content-Type'] = 'application/json';
  }

  if (options.token) {
    headers.Authorization = `Bearer ${options.token}`;
  }

  const response = await fetch(path, {
    method: options.method ?? 'GET',
    headers,
    body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
  });

  const data: unknown = await response.json().catch(() => null);

  if (!response.ok) {
    const message =
      data !== null && typeof data === 'object' && 'message' in data && typeof data.message === 'string'
        ? data.message
        : 'Заявката не беше успешна.';

    throw new ApiError(message, response.status, data);
  }

  return data as T;
}
