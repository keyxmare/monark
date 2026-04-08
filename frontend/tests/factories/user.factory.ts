import type { User } from '@/identity/types/user';

export function createUser(overrides?: Partial<User>): User {
  return {
    id: 'user-1',
    email: 'john.doe@example.com',
    firstName: 'John',
    lastName: 'Doe',
    avatar: null,
    roles: ['ROLE_USER'],
    createdAt: '2025-01-01T00:00:00+00:00',
    updatedAt: '2025-01-01T00:00:00+00:00',
    ...overrides,
  };
}
