import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useAccessTokenStore } from '@/identity/stores/access-token';

vi.mock('@/identity/services/access-token.service', () => ({
  accessTokenService: {
    list: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
    remove: vi.fn(),
  },
}));

import { accessTokenService } from '@/identity/services/access-token.service';

const mockToken = {
  id: '456',
  provider: 'gitlab' as const,
  scopes: ['read_api'],
  expiresAt: null,
  userId: '123',
  createdAt: '2026-01-01T00:00:00+00:00',
};

describe('Access Token Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetches all tokens', async () => {
    vi.mocked(accessTokenService.list).mockResolvedValue({
      data: {
        items: [mockToken],
        total: 1,
        page: 1,
        per_page: 20,
        total_pages: 1,
      },
      status: 200,
    });

    const store = useAccessTokenStore();
    await store.fetchAll();

    expect(store.tokens).toHaveLength(1);
    expect(store.tokens[0].provider).toBe('gitlab');
  });

  it('creates a token', async () => {
    vi.mocked(accessTokenService.create).mockResolvedValue({
      data: mockToken,
      status: 201,
    });

    const store = useAccessTokenStore();
    const result = await store.create({
      provider: 'gitlab',
      token: 'glpat-test',
      scopes: ['read_api'],
    });

    expect(result.id).toBe('456');
    expect(store.tokens).toHaveLength(1);
  });

  it('removes a token', async () => {
    vi.mocked(accessTokenService.remove).mockResolvedValue(undefined);

    const store = useAccessTokenStore();
    store.tokens = [mockToken];

    await store.remove('456');

    expect(store.tokens).toHaveLength(0);
  });

  it('sets error on fetch failure', async () => {
    vi.mocked(accessTokenService.list).mockRejectedValue(new Error('Network error'));

    const store = useAccessTokenStore();
    await store.fetchAll();

    expect(store.error).toBe('Failed to load access tokens');
  });
});
