import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
  useRoute: vi.fn(() => ({ params: {}, query: {} })),
  useRouter: vi.fn(() => ({ push: vi.fn() })),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string, params?: Record<string, string>) => key }),
}));

vi.mock('@/shared/layouts/DashboardLayout.vue', () => ({
  default: { template: '<div><slot /></div>' },
}));

vi.mock('@/shared/components/ConfirmDialog.vue', () => ({
  default: { template: '<div />' },
}));

vi.mock('@/catalog/components/ProviderCard.vue', () => ({
  default: { props: ['provider', 'items'], template: '<div data-testid="provider-card" />' },
}));

vi.mock('@/shared/composables/useConfirmDelete', () => ({
  useConfirmDelete: () => ({
    cancel: vi.fn(),
    confirm: vi.fn(),
    isOpen: false,
    requestDelete: vi.fn(),
    target: null,
  }),
}));

vi.mock('@/catalog/composables/useSyncProgress', () => ({
  useSyncProgress: () => ({
    track: vi.fn(),
  }),
}));

vi.mock('@/shared/stores/toast', () => ({
  useToastStore: vi.fn(() => ({
    addToast: vi.fn(),
    toasts: [],
  })),
}));

const mockFetchAll = vi.fn();
let storeOverrides: Record<string, unknown> = {};

vi.mock('@/catalog/stores/provider', () => ({
  useProviderStore: vi.fn(() => ({
    currentPage: 1,
    error: null,
    fetchAll: mockFetchAll,
    loading: false,
    providers: [],
    remove: vi.fn(),
    syncAllGlobal: vi.fn(),
    testConnection: vi.fn(),
    total: 0,
    totalPages: 0,
    ...storeOverrides,
  })),
}));

import ProviderList from '@/catalog/pages/ProviderList.vue';

describe('ProviderList', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    storeOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(ProviderList);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="provider-list-page"]').exists()).toBe(true);
  });

  it('calls fetchAll on mount', () => {
    mount(ProviderList);
    expect(mockFetchAll).toHaveBeenCalled();
  });

  it('shows loading state', () => {
    storeOverrides = { loading: true };
    const wrapper = mount(ProviderList);
    expect(wrapper.find('[data-testid="provider-list-loading"]').exists()).toBe(true);
  });

  it('shows error state', () => {
    storeOverrides = { error: 'Connection failed' };
    const wrapper = mount(ProviderList);
    expect(wrapper.find('[data-testid="provider-list-error"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="provider-list-error"]').text()).toContain(
      'Connection failed',
    );
  });

  it('shows empty state when no providers', () => {
    const wrapper = mount(ProviderList);
    expect(wrapper.find('[data-testid="provider-list-empty"]').exists()).toBe(true);
  });

  it('renders provider cards when providers exist', () => {
    storeOverrides = {
      providers: [
        { id: '1', name: 'GitLab', type: 'gitlab', status: 'connected' },
        { id: '2', name: 'GitHub', type: 'github', status: 'pending' },
      ],
    };
    const wrapper = mount(ProviderList);
    const cards = wrapper.findAll('[data-testid="provider-card"]');
    expect(cards).toHaveLength(2);
  });

  it('renders create provider link', () => {
    const wrapper = mount(ProviderList);
    expect(wrapper.find('[data-testid="provider-create-link"]').exists()).toBe(true);
  });

  it('renders sync all button', () => {
    const wrapper = mount(ProviderList);
    expect(wrapper.find('[data-testid="provider-sync-all-global"]').exists()).toBe(true);
  });
});
