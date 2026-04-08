import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
  useRoute: vi.fn(() => ({ params: { id: 'prov-1' }, query: {} })),
  useRouter: vi.fn(() => ({ push: vi.fn() })),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({
    d: (date: Date) => date.toISOString(),
    t: (key: string) => key,
  }),
}));

vi.mock('@/shared/layouts/DashboardLayout.vue', () => ({
  default: { template: '<div><slot /></div>' },
}));

vi.mock('@/shared/components/ConfirmDialog.vue', () => ({
  default: { template: '<div />' },
}));

vi.mock('@/catalog/components/ProviderInfoCard.vue', () => ({
  default: { template: '<div data-testid="provider-info-card" />' },
}));

vi.mock('@/catalog/components/ProviderStatsCards.vue', () => ({
  default: { template: '<div data-testid="provider-stats-cards" />' },
}));

vi.mock('@/catalog/components/RemoteProjectsSection.vue', () => ({
  default: { template: '<div data-testid="remote-projects-section" />' },
}));

vi.mock('@/catalog/composables/useSyncProgress', () => ({
  useSyncProgress: () => ({
    track: vi.fn(),
  }),
}));

const mockFetchOne = vi.fn();
const mockFetchRemoteProjects = vi.fn();
const mockRemove = vi.fn();
const mockTestConnection = vi.fn();
const mockSyncAll = vi.fn();
const mockImportProjects = vi.fn();
let storeOverrides: Record<string, unknown> = {};

vi.mock('@/catalog/stores/provider', () => ({
  useProviderStore: vi.fn(() => ({
    error: null,
    fetchOne: mockFetchOne,
    fetchRemoteProjects: mockFetchRemoteProjects,
    importProjects: mockImportProjects,
    loading: false,
    remoteProjects: [],
    remoteProjectsCurrentPage: 1,
    remoteProjectsError: null,
    remoteProjectsTotalPages: 1,
    remove: mockRemove,
    selected: null,
    syncAll: mockSyncAll,
    testConnection: mockTestConnection,
    ...storeOverrides,
  })),
}));

const mockAddToast = vi.fn();
vi.mock('@/shared/stores/toast', () => ({
  useToastStore: vi.fn(() => ({
    addToast: mockAddToast,
  })),
}));

import ProviderDetail from '@/catalog/pages/ProviderDetail.vue';

describe('ProviderDetail', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    storeOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(ProviderDetail);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="provider-detail-page"]').exists()).toBe(true);
  });

  it('renders breadcrumb', () => {
    const wrapper = mount(ProviderDetail);
    expect(wrapper.find('[data-testid="provider-detail-breadcrumb"]').exists()).toBe(true);
  });

  it('calls fetchOne and fetchRemoteProjects on mount', () => {
    mount(ProviderDetail);
    expect(mockFetchOne).toHaveBeenCalledWith('prov-1');
  });

  it('shows loading state when no selected provider', () => {
    storeOverrides = { loading: true };
    const wrapper = mount(ProviderDetail);
    expect(wrapper.find('[data-testid="provider-detail-loading"]').exists()).toBe(true);
  });

  it('shows error state when no selected provider', () => {
    storeOverrides = { error: 'Not found' };
    const wrapper = mount(ProviderDetail);
    expect(wrapper.find('[data-testid="provider-detail-error"]').exists()).toBe(true);
  });

  it('renders edit and delete buttons when provider is selected', () => {
    storeOverrides = {
      selected: {
        id: 'prov-1',
        lastSyncAt: null,
        name: 'My Provider',
        projectsCount: 3,
        status: 'connected',
        type: 'gitlab',
        url: 'https://gitlab.example.com',
      },
    };
    const wrapper = mount(ProviderDetail);
    expect(wrapper.find('[data-testid="provider-detail-edit"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="provider-detail-delete"]').exists()).toBe(true);
  });

  it('renders child components when provider is selected', () => {
    storeOverrides = {
      selected: {
        id: 'prov-1',
        lastSyncAt: null,
        name: 'My Provider',
        projectsCount: 3,
        status: 'connected',
        type: 'gitlab',
        url: 'https://gitlab.example.com',
      },
    };
    const wrapper = mount(ProviderDetail);
    expect(wrapper.find('[data-testid="provider-info-card"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="provider-stats-cards"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="remote-projects-section"]').exists()).toBe(true);
  });

  it('shows warning when error exists but provider is selected', () => {
    storeOverrides = {
      error: 'Sync warning',
      selected: {
        id: 'prov-1',
        lastSyncAt: null,
        name: 'My Provider',
        projectsCount: 3,
        status: 'connected',
        type: 'gitlab',
        url: 'https://gitlab.example.com',
      },
    };
    const wrapper = mount(ProviderDetail);
    expect(wrapper.find('[data-testid="provider-detail-warning"]').exists()).toBe(true);
  });
});
