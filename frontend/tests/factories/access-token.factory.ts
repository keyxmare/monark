import type { AccessToken } from '@/identity/types/access-token';

export function createAccessToken(overrides?: Partial<AccessToken>): AccessToken {
  return {
    id: 'token-1',
    provider: 'github',
    scopes: ['repo', 'read:org'],
    expiresAt: null,
    userId: 'user-1',
    createdAt: '2025-01-01T00:00:00+00:00',
    ...overrides,
  };
}
