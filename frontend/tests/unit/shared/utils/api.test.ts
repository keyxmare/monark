import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/shared/i18n', () => ({
  i18n: {
    global: {
      locale: { value: 'en' },
    },
  },
}));

const originalFetch = globalThis.fetch;
const originalLocation = window.location;

import { api } from '@/shared/utils/api';

describe('api utility', () => {
  let mockFetch: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    mockFetch = vi.fn();
    globalThis.fetch = mockFetch;
    localStorage.clear();
    // Mock window.location
    Object.defineProperty(window, 'location', {
      configurable: true,
      value: { href: '' },
      writable: true,
    });
  });

  afterEach(() => {
    globalThis.fetch = originalFetch;
    Object.defineProperty(window, 'location', {
      configurable: true,
      value: originalLocation,
      writable: true,
    });
  });

  it('api.get sends GET request', async () => {
    mockFetch.mockResolvedValueOnce({
      json: () => Promise.resolve({ data: 'ok' }),
      ok: true,
      status: 200,
    });

    const result = await api.get('/test');
    expect(result).toEqual({ data: 'ok' });
    expect(mockFetch).toHaveBeenCalledWith(
      expect.stringContaining('/test'),
      expect.objectContaining({
        headers: expect.objectContaining({
          Accept: 'application/json',
          'Content-Type': 'application/json',
        }),
      }),
    );
  });

  it('api.post sends POST request with body', async () => {
    mockFetch.mockResolvedValueOnce({
      json: () => Promise.resolve({ id: '1' }),
      ok: true,
      status: 201,
    });

    const result = await api.post('/items', { name: 'test' });
    expect(result).toEqual({ id: '1' });
    expect(mockFetch).toHaveBeenCalledWith(
      expect.stringContaining('/items'),
      expect.objectContaining({
        body: JSON.stringify({ name: 'test' }),
        method: 'POST',
      }),
    );
  });

  it('api.put sends PUT request with body', async () => {
    mockFetch.mockResolvedValueOnce({
      json: () => Promise.resolve({ id: '1' }),
      ok: true,
      status: 200,
    });

    const result = await api.put('/items/1', { name: 'updated' });
    expect(result).toEqual({ id: '1' });
    expect(mockFetch).toHaveBeenCalledWith(
      expect.stringContaining('/items/1'),
      expect.objectContaining({
        body: JSON.stringify({ name: 'updated' }),
        method: 'PUT',
      }),
    );
  });

  it('api.patch sends PATCH request with body', async () => {
    mockFetch.mockResolvedValueOnce({
      json: () => Promise.resolve({ id: '1' }),
      ok: true,
      status: 200,
    });

    const result = await api.patch('/items/1', { name: 'patched' });
    expect(result).toEqual({ id: '1' });
    expect(mockFetch).toHaveBeenCalledWith(
      expect.stringContaining('/items/1'),
      expect.objectContaining({
        body: JSON.stringify({ name: 'patched' }),
        method: 'PATCH',
      }),
    );
  });

  it('api.delete sends DELETE request', async () => {
    mockFetch.mockResolvedValueOnce({
      json: () => Promise.resolve(undefined),
      ok: true,
      status: 204,
    });

    const result = await api.delete('/items/1');
    expect(result).toBeUndefined();
    expect(mockFetch).toHaveBeenCalledWith(
      expect.stringContaining('/items/1'),
      expect.objectContaining({
        method: 'DELETE',
      }),
    );
  });

  it('includes Authorization header when token exists', async () => {
    localStorage.setItem('auth_token', 'my-jwt-token');
    mockFetch.mockResolvedValueOnce({
      json: () => Promise.resolve({}),
      ok: true,
      status: 200,
    });

    await api.get('/protected');
    expect(mockFetch).toHaveBeenCalledWith(
      expect.any(String),
      expect.objectContaining({
        headers: expect.objectContaining({
          Authorization: 'Bearer my-jwt-token',
        }),
      }),
    );
  });

  it('handles 204 No Content response', async () => {
    mockFetch.mockResolvedValueOnce({
      json: () => Promise.reject(new Error('no body')),
      ok: true,
      status: 204,
    });

    const result = await api.delete('/items/1');
    expect(result).toBeUndefined();
  });

  it('handles 401 Unauthorized by clearing token and redirecting', async () => {
    localStorage.setItem('auth_token', 'expired-token');
    mockFetch.mockResolvedValueOnce({
      json: () => Promise.resolve({ message: 'Unauthorized', status: 401 }),
      ok: false,
      status: 401,
    });

    await expect(api.get('/protected')).rejects.toEqual(
      expect.objectContaining({ message: 'Session expired', status: 401 }),
    );
    expect(localStorage.getItem('auth_token')).toBeNull();
    expect(window.location.href).toBe('/login');
  });

  it('handles generic error response', async () => {
    mockFetch.mockResolvedValueOnce({
      json: () => Promise.resolve({ message: 'Not found', status: 404 }),
      ok: false,
      status: 404,
      statusText: 'Not Found',
    });

    await expect(api.get('/missing')).rejects.toEqual(
      expect.objectContaining({ message: 'Not found', status: 404 }),
    );
  });

  it('handles error when response body is not JSON', async () => {
    mockFetch.mockResolvedValueOnce({
      json: () => Promise.reject(new Error('not JSON')),
      ok: false,
      status: 500,
      statusText: 'Internal Server Error',
    });

    await expect(api.get('/broken')).rejects.toEqual(
      expect.objectContaining({ message: 'Internal Server Error', status: 500 }),
    );
  });
});
