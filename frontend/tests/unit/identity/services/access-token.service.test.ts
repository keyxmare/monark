import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/shared/utils/api', () => ({
  api: { delete: vi.fn(), get: vi.fn(), patch: vi.fn(), post: vi.fn(), put: vi.fn() },
}));

import { api } from '@/shared/utils/api';
import { accessTokenService } from '@/identity/services/access-token.service';

describe('accessTokenService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('list calls GET /identity/access-tokens with default pagination', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });

    await accessTokenService.list();

    expect(api.get).toHaveBeenCalledWith('/identity/access-tokens?page=1&per_page=20');
  });

  it('list calls GET /identity/access-tokens with custom pagination', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });

    await accessTokenService.list(2, 10);

    expect(api.get).toHaveBeenCalledWith('/identity/access-tokens?page=2&per_page=10');
  });

  it('get calls GET /identity/access-tokens/:id', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { id: 'tok-1' }, status: 200 });

    await accessTokenService.get('tok-1');

    expect(api.get).toHaveBeenCalledWith('/identity/access-tokens/tok-1');
  });

  it('create calls POST /identity/access-tokens with data', async () => {
    const data = { name: 'My Token', scopes: ['read'] };
    vi.mocked(api.post).mockResolvedValue({ data: { id: 'tok-1' }, status: 201 });

    await accessTokenService.create(data);

    expect(api.post).toHaveBeenCalledWith('/identity/access-tokens', data);
  });

  it('remove calls DELETE /identity/access-tokens/:id', async () => {
    vi.mocked(api.delete).mockResolvedValue(undefined);

    await accessTokenService.remove('tok-1');

    expect(api.delete).toHaveBeenCalledWith('/identity/access-tokens/tok-1');
  });
});
