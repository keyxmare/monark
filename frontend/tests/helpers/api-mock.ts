import { vi } from 'vitest';

export function createApiMock(responses: Record<string, unknown>): void {
  vi.stubGlobal(
    'fetch',
    vi.fn(async (input: string | URL | Request, init?: RequestInit) => {
      const url = typeof input === 'string' ? input : input.toString();
      const method = (init?.method ?? 'GET').toUpperCase();

      // Strip /api/v1 prefix
      const stripped = url.replace(/^\/api\/v1/, '');

      // Try exact match first (with query params)
      const exactKey = `${method} ${stripped}`;
      if (exactKey in responses) {
        return new Response(JSON.stringify(responses[exactKey]), {
          status: 200,
          headers: { 'Content-Type': 'application/json' },
        });
      }

      // Strip query params and try again
      const pathOnly = stripped.split('?')[0];
      const baseKey = `${method} ${pathOnly}`;
      if (baseKey in responses) {
        return new Response(JSON.stringify(responses[baseKey]), {
          status: 200,
          headers: { 'Content-Type': 'application/json' },
        });
      }

      // 404 for unmocked routes
      return new Response(JSON.stringify({ error: 'Not Found' }), {
        status: 404,
        headers: { 'Content-Type': 'application/json' },
      });
    }),
  );
}

export function resetApiMock(): void {
  vi.unstubAllGlobals();
}
