import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
  useRoute: vi.fn(() => ({ params: {}, query: {} })),
  useRouter: vi.fn(() => ({ push: vi.fn() })),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({
    d: (date: Date) => date.toISOString(),
    t: (key: string, params?: Record<string, string>) => key,
  }),
}));

vi.mock('@/shared/layouts/DashboardLayout.vue', () => ({
  default: { template: '<div><slot /></div>' },
}));

const mockFetchAll = vi.fn();
const mockRemove = vi.fn();
let storeOverrides: Record<string, unknown> = {};

vi.mock('@/identity/stores/access-token', () => ({
  useAccessTokenStore: vi.fn(() => ({
    error: null,
    fetchAll: mockFetchAll,
    loading: false,
    remove: mockRemove,
    tokens: [],
    ...storeOverrides,
  })),
}));

import AccessTokenList from '@/identity/pages/AccessTokenList.vue';

describe('AccessTokenList', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    storeOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(AccessTokenList);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="access-token-list-page"]').exists()).toBe(true);
  });

  it('calls fetchAll on mount', () => {
    mount(AccessTokenList);
    expect(mockFetchAll).toHaveBeenCalled();
  });

  it('renders create link', () => {
    const wrapper = mount(AccessTokenList);
    expect(wrapper.find('[data-testid="access-token-create-link"]').exists()).toBe(true);
  });

  it('shows loading state', () => {
    storeOverrides = { loading: true };
    const wrapper = mount(AccessTokenList);
    expect(wrapper.find('[data-testid="access-token-list-loading"]').exists()).toBe(true);
  });

  it('shows error state', () => {
    storeOverrides = { error: 'Failed to load' };
    const wrapper = mount(AccessTokenList);
    expect(wrapper.find('[data-testid="access-token-list-error"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="access-token-list-error"]').text()).toContain(
      'Failed to load',
    );
  });

  it('shows empty state when no tokens', () => {
    const wrapper = mount(AccessTokenList);
    expect(wrapper.find('[data-testid="access-token-list-empty"]').exists()).toBe(true);
  });

  it('renders token rows when data exists', () => {
    storeOverrides = {
      tokens: [
        {
          id: 'tok-1',
          provider: 'github',
          scopes: ['repo'],
          expiresAt: null,
          userId: 'user-1',
          createdAt: '2025-01-01T00:00:00+00:00',
        },
        {
          id: 'tok-2',
          provider: 'gitlab',
          scopes: ['read_api'],
          expiresAt: '2025-12-31T00:00:00+00:00',
          userId: 'user-1',
          createdAt: '2025-01-01T00:00:00+00:00',
        },
      ],
    };
    const wrapper = mount(AccessTokenList);
    const rows = wrapper.findAll('[data-testid="access-token-list-row"]');
    expect(rows).toHaveLength(2);
  });

  it('renders delete buttons on token rows', () => {
    storeOverrides = {
      tokens: [
        {
          id: 'tok-1',
          provider: 'github',
          scopes: ['repo'],
          expiresAt: null,
          userId: 'user-1',
          createdAt: '2025-01-01T00:00:00+00:00',
        },
      ],
    };
    const wrapper = mount(AccessTokenList);
    expect(wrapper.find('[data-testid="access-token-delete"]').exists()).toBe(true);
  });

  it('renders the table', () => {
    const wrapper = mount(AccessTokenList);
    expect(wrapper.find('[data-testid="access-token-list-table"]').exists()).toBe(true);
  });
});
