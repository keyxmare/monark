import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
  useRoute: vi.fn(() => ({ params: {}, query: {} })),
  useRouter: vi.fn(() => ({ push: vi.fn() })),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string) => key }),
}));

vi.mock('@/shared/layouts/DashboardLayout.vue', () => ({
  default: { template: '<div><slot /></div>' },
}));

vi.mock('@/shared/components/Pagination.vue', () => ({
  default: { template: '<div />' },
}));

vi.mock('@/shared/components/TechBadge.vue', () => ({
  default: { props: ['name', 'version', 'size'], template: '<span />' },
}));

const mockLanguageFetchAll = vi.fn();
let languageStoreOverrides: Record<string, unknown> = {};

vi.mock('@/catalog/stores/language', () => ({
  useLanguageStore: vi.fn(() => ({
    currentPage: 1,
    error: null,
    fetchAll: mockLanguageFetchAll,
    languages: [],
    loading: false,
    total: 0,
    totalPages: 0,
    ...languageStoreOverrides,
  })),
}));

const mockProjectFetchAll = vi.fn();
vi.mock('@/catalog/stores/project', () => ({
  useProjectStore: vi.fn(() => ({
    fetchAll: mockProjectFetchAll,
    projects: [],
  })),
}));

vi.mock('@/catalog/composables/useLanguageFiltering', () => ({
  useLanguageFiltering: () => ({
    availableLanguages: [],
    filterLanguage: { value: '' },
    filterStatus: { value: '' },
    filteredLanguages: { value: [] },
    groupedLanguages: [],
    search: { value: '' },
    sortIndicator: vi.fn(() => ''),
    toggleSort: vi.fn(),
  }),
}));

vi.mock('@/shared/composables/useGlobalSync', () => ({
  useGlobalSync: () => ({
    currentSync: { value: null },
    isRunning: { value: false },
    startSync: vi.fn(),
    loadCurrent: vi.fn(),
    onStepCompleted: vi.fn(),
  }),
}));

vi.mock('@/shared/components/SyncButton.vue', () => ({
  default: { template: '<button data-testid="sync-button" />' },
}));

import LanguageList from '@/catalog/pages/LanguageList.vue';

describe('LanguageList', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    languageStoreOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(LanguageList);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="language-list-page"]').exists()).toBe(true);
  });

  it('renders breadcrumb', () => {
    const wrapper = mount(LanguageList);
    expect(wrapper.find('[data-testid="language-list-breadcrumb"]').exists()).toBe(true);
  });

  it('calls fetchAll on mount', () => {
    mount(LanguageList);
    expect(mockLanguageFetchAll).toHaveBeenCalled();
    expect(mockProjectFetchAll).toHaveBeenCalled();
  });

  it('shows loading state', () => {
    languageStoreOverrides = { loading: true };
    const wrapper = mount(LanguageList);
    expect(wrapper.find('[data-testid="language-list-loading"]').exists()).toBe(true);
  });

  it('shows error state', () => {
    languageStoreOverrides = { error: 'Network error' };
    const wrapper = mount(LanguageList);
    expect(wrapper.find('[data-testid="language-list-error"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="language-list-error"]').text()).toContain('Network error');
  });

  it('shows empty state when no languages', () => {
    const wrapper = mount(LanguageList);
    expect(wrapper.find('[data-testid="language-list-empty"]').exists()).toBe(true);
  });

  it('shows empty providers link', () => {
    const wrapper = mount(LanguageList);
    expect(wrapper.find('[data-testid="language-empty-providers-link"]').exists()).toBe(true);
  });

  it('shows filters when not loading', () => {
    const wrapper = mount(LanguageList);
    expect(wrapper.find('[data-testid="language-filters"]').exists()).toBe(true);
  });
});
