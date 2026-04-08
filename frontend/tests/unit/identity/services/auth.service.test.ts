import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/shared/utils/api', () => ({
  api: { delete: vi.fn(), get: vi.fn(), patch: vi.fn(), post: vi.fn(), put: vi.fn() },
}));

import { api } from '@/shared/utils/api';
import { authService } from '@/identity/services/auth.service';

describe('authService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('register calls POST /auth/register with user data', async () => {
    const input = { email: 'test@example.com', password: 'secret', name: 'Test' };
    vi.mocked(api.post).mockResolvedValue({ data: {}, status: 201 });

    await authService.register(input);

    expect(api.post).toHaveBeenCalledWith('/auth/register', input);
  });

  it('login calls POST /auth/login with email and password', async () => {
    vi.mocked(api.post).mockResolvedValue({ data: { token: 'abc' }, status: 200 });

    await authService.login('test@example.com', 'secret');

    expect(api.post).toHaveBeenCalledWith('/auth/login', {
      email: 'test@example.com',
      password: 'secret',
    });
  });

  it('logout calls POST /auth/logout with empty body', async () => {
    vi.mocked(api.post).mockResolvedValue(undefined);

    await authService.logout();

    expect(api.post).toHaveBeenCalledWith('/auth/logout', {});
  });

  it('getCurrentUser calls GET /auth/profile', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { id: '1' }, status: 200 });

    await authService.getCurrentUser();

    expect(api.get).toHaveBeenCalledWith('/auth/profile');
  });
});
