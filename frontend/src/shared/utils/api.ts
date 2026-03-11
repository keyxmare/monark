import type { ApiError, ApiResponse } from '@/shared/types'

const BASE_URL = import.meta.env.VITE_API_BASE_URL ?? '/api'

async function request<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
  const url = `${BASE_URL}${endpoint}`
  const headers: HeadersInit = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    ...options.headers,
  }

  const token = localStorage.getItem('auth_token')
  if (token) {
    ;(headers as Record<string, string>).Authorization = `Bearer ${token}`
  }

  const response = await fetch(url, { ...options, headers })

  if (!response.ok) {
    const error: ApiError = await response.json().catch(() => ({
      message: response.statusText,
      status: response.status,
    }))
    throw error
  }

  if (response.status === 204) {
    return undefined as T
  }

  return response.json() as Promise<T>
}

export const api = {
  delete<T>(endpoint: string): Promise<T> {
    return request<T>(endpoint, { method: 'DELETE' })
  },

  get<T>(endpoint: string): Promise<T> {
    return request<T>(endpoint)
  },

  patch<T>(endpoint: string, body: unknown): Promise<T> {
    return request<T>(endpoint, { body: JSON.stringify(body), method: 'PATCH' })
  },

  post<T>(endpoint: string, body: unknown): Promise<T> {
    return request<T>(endpoint, { body: JSON.stringify(body), method: 'POST' })
  },

  put<T>(endpoint: string, body: unknown): Promise<T> {
    return request<T>(endpoint, { body: JSON.stringify(body), method: 'PUT' })
  },
}
