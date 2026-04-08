import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useAuthStore } from '@/identity/stores/auth';

vi.mock('@/identity/services/auth.service', () => ({
  authService: {
    login: vi.fn(),
    register: vi.fn(),
    logout: vi.fn(),
    getCurrentUser: vi.fn(),
  },
}));

import { authService } from '@/identity/services/auth.service';

const mockUser = {
  id: '123',
  email: 'john@example.com',
  firstName: 'John',
  lastName: 'Doe',
  avatar: null,
  roles: ['ROLE_USER'],
  createdAt: '2026-01-01T00:00:00+00:00',
  updatedAt: '2026-01-01T00:00:00+00:00',
};

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    localStorage.clear();
  });

  it('starts with no user and unauthenticated', () => {
    const store = useAuthStore();
    expect(store.currentUser).toBeNull();
    expect(store.isAuthenticated).toBe(false);
  });

  it('logs in successfully', async () => {
    vi.mocked(authService.login).mockResolvedValue({
      data: { token: 'test-token', user: mockUser },
      status: 200,
    });

    const store = useAuthStore();
    await store.login('john@example.com', 'password123');

    expect(store.token).toBe('test-token');
    expect(store.currentUser).toEqual(mockUser);
    expect(store.isAuthenticated).toBe(true);
    expect(localStorage.getItem('auth_token')).toBe('test-token');
  });

  it('sets error on login failure', async () => {
    vi.mocked(authService.login).mockRejectedValue(new Error('Invalid'));

    const store = useAuthStore();

    await expect(store.login('bad@example.com', 'wrong')).rejects.toThrow();
    expect(store.error).toBe('Invalid credentials');
    expect(store.isAuthenticated).toBe(false);
  });

  it('registers successfully', async () => {
    vi.mocked(authService.register).mockResolvedValue({
      data: mockUser,
      status: 201,
    });

    const store = useAuthStore();
    await store.register('john@example.com', 'password123', 'John', 'Doe');

    expect(authService.register).toHaveBeenCalledWith({
      email: 'john@example.com',
      password: 'password123',
      firstName: 'John',
      lastName: 'Doe',
    });
  });

  it('logs out and clears state', async () => {
    vi.mocked(authService.logout).mockResolvedValue(undefined);

    const store = useAuthStore();
    store.token = 'test-token';
    store.currentUser = mockUser;

    await store.logout();

    expect(store.token).toBeNull();
    expect(store.currentUser).toBeNull();
    expect(localStorage.getItem('auth_token')).toBeNull();
  });

  it('fetches current user', async () => {
    vi.mocked(authService.getCurrentUser).mockResolvedValue({
      data: mockUser,
      status: 200,
    });

    const store = useAuthStore();
    store.token = 'test-token';
    await store.fetchCurrentUser();

    expect(store.currentUser).toEqual(mockUser);
  });

  it('clears token when fetchCurrentUser fails', async () => {
    vi.mocked(authService.getCurrentUser).mockRejectedValue(new Error('Unauthorized'));

    const store = useAuthStore();
    store.token = 'invalid-token';
    await store.fetchCurrentUser();

    expect(store.token).toBeNull();
    expect(store.currentUser).toBeNull();
  });
});
