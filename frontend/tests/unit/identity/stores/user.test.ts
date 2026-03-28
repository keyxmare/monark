import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useUserStore } from '@/identity/stores/user';

vi.mock('@/identity/services/user.service', () => ({
  userService: {
    list: vi.fn(),
    get: vi.fn(),
    update: vi.fn(),
  },
}));

import { userService } from '@/identity/services/user.service';

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

describe('User Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetches all users', async () => {
    vi.mocked(userService.list).mockResolvedValue({
      data: {
        items: [mockUser],
        total: 1,
        page: 1,
        per_page: 20,
        total_pages: 1,
      },
      status: 200,
    });

    const store = useUserStore();
    await store.fetchAll();

    expect(store.users).toHaveLength(1);
    expect(store.users[0].email).toBe('john@example.com');
    expect(store.total).toBe(1);
  });

  it('fetches a single user', async () => {
    vi.mocked(userService.get).mockResolvedValue({
      data: mockUser,
      status: 200,
    });

    const store = useUserStore();
    await store.fetchOne('123');

    expect(store.selectedUser).toEqual(mockUser);
  });

  it('updates a user', async () => {
    const updatedUser = { ...mockUser, firstName: 'Jane' };
    vi.mocked(userService.update).mockResolvedValue({
      data: updatedUser,
      status: 200,
    });

    const store = useUserStore();
    store.users = [mockUser];
    await store.update('123', { firstName: 'Jane' });

    expect(store.selectedUser?.firstName).toBe('Jane');
    expect(store.users[0].firstName).toBe('Jane');
  });

  it('sets error on fetch failure', async () => {
    vi.mocked(userService.list).mockRejectedValue(new Error('Network error'));

    const store = useUserStore();
    await store.fetchAll();

    expect(store.error).toBe('Failed to load users');
  });
});
