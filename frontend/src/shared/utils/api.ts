import type { WritableComputedRef } from 'vue';

import type { ApiError, ApiResponse } from '@/shared/types';
import { STORAGE_KEYS } from '@/shared/constants';
import { i18n } from '@/shared/i18n';

const BASE_URL = import.meta.env.VITE_API_BASE_URL ?? '/api';

async function request<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
  const url = `${BASE_URL}${endpoint}`;
  const headers: HeadersInit = {
    Accept: 'application/json',
    'Accept-Language': (i18n.global.locale as unknown as WritableComputedRef<string>).value,
    'Content-Type': 'application/json',
    ...options.headers,
  };

  const token = localStorage.getItem(STORAGE_KEYS.AUTH_TOKEN);
  if (token) {
    (headers as Record<string, string>).Authorization = `Bearer ${token}`;
  }

  const response = await fetch(url, { ...options, headers });

  if (!response.ok) {
    if (response.status === 401) {
      localStorage.removeItem(STORAGE_KEYS.AUTH_TOKEN);
      window.location.href = '/login';
      throw { message: 'Session expired', status: 401 } as ApiError;
    }

    const error: ApiError = await response.json().catch(() => ({
      message: response.statusText,
      status: response.status,
    }));
    throw error;
  }

  if (response.status === 204) {
    return undefined as T;
  }

  return response.json() as Promise<T>;
}

export const api = {
  delete<T>(endpoint: string): Promise<T> {
    return request<T>(endpoint, { method: 'DELETE' });
  },

  get<T>(endpoint: string): Promise<T> {
    return request<T>(endpoint);
  },

  patch<T>(endpoint: string, body: unknown): Promise<T> {
    return request<T>(endpoint, { body: JSON.stringify(body), method: 'PATCH' });
  },

  post<T>(endpoint: string, body: unknown): Promise<T> {
    return request<T>(endpoint, { body: JSON.stringify(body), method: 'POST' });
  },

  put<T>(endpoint: string, body: unknown): Promise<T> {
    return request<T>(endpoint, { body: JSON.stringify(body), method: 'PUT' });
  },
};
