import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/shared/utils/api', () => ({
  api: { delete: vi.fn(), get: vi.fn(), patch: vi.fn(), post: vi.fn(), put: vi.fn() },
}));

import { api } from '@/shared/utils/api';
import { userService } from '@/identity/services/user.service';

describe('userService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('list calls GET /identity/users with default pagination', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });

    await userService.list();

    expect(api.get).toHaveBeenCalledWith('/identity/users?page=1&per_page=20');
  });

  it('list calls GET /identity/users with custom pagination', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });

    await userService.list(3, 50);

    expect(api.get).toHaveBeenCalledWith('/identity/users?page=3&per_page=50');
  });

  it('get calls GET /identity/users/:id', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { id: 'abc' }, status: 200 });

    await userService.get('abc');

    expect(api.get).toHaveBeenCalledWith('/identity/users/abc');
  });

  it('update calls PUT /identity/users/:id with data', async () => {
    const data = { name: 'Updated' };
    vi.mocked(api.put).mockResolvedValue({ data: { id: 'abc' }, status: 200 });

    await userService.update('abc', data);

    expect(api.put).toHaveBeenCalledWith('/identity/users/abc', data);
  });
});
