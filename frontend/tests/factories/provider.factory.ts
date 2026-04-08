import type { Provider } from '@/catalog/types/provider';

export function createProvider(overrides?: Partial<Provider>): Provider {
  return {
    id: 'provider-1',
    name: 'GitHub',
    type: 'github',
    url: 'https://api.github.com',
    username: null,
    status: 'connected',
    projectsCount: 5,
    lastSyncAt: null,
    createdAt: '2025-01-01T00:00:00+00:00',
    updatedAt: '2025-01-01T00:00:00+00:00',
    ...overrides,
  };
}
