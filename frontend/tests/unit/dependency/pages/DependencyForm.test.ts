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

const mockCreate = vi.fn();
const mockUpdate = vi.fn();
const mockFetchOne = vi.fn();
let depStoreOverrides: Record<string, unknown> = {};

vi.mock('@/dependency/stores/dependency', () => ({
  useDependencyStore: vi.fn(() => ({
    create: mockCreate,
    update: mockUpdate,
    fetchOne: mockFetchOne,
    selectedDependency: null,
    error: null,
    loading: false,
    ...depStoreOverrides,
  })),
}));

const mockProjectFetchAll = vi.fn();
let projectStoreOverrides: Record<string, unknown> = {};

vi.mock('@/catalog/stores/project', () => ({
  useProjectStore: vi.fn(() => ({
    fetchAll: mockProjectFetchAll,
    projects: [],
    ...projectStoreOverrides,
  })),
}));

import DependencyForm from '@/dependency/pages/DependencyForm.vue';

describe('DependencyForm', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    depStoreOverrides = {};
    projectStoreOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(DependencyForm);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="dependency-form-page"]').exists()).toBe(true);
  });

  it('renders the form with all fields', () => {
    const wrapper = mount(DependencyForm);
    expect(wrapper.find('[data-testid="dependency-form"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="dependency-form-name"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="dependency-form-current-version"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="dependency-form-latest-version"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="dependency-form-lts-version"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="dependency-form-package-manager"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="dependency-form-type"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="dependency-form-is-outdated"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="dependency-form-repository-url"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="dependency-form-submit"]').exists()).toBe(true);
  });

  it('shows project select in create mode', () => {
    const wrapper = mount(DependencyForm);
    expect(wrapper.find('[data-testid="dependency-form-project-id"]').exists()).toBe(true);
  });

  it('fetches projects on mount', () => {
    mount(DependencyForm);
    expect(mockProjectFetchAll).toHaveBeenCalledWith(1, 200);
  });

  it('renders project options when projects exist', () => {
    projectStoreOverrides = {
      projects: [
        { id: 'p1', name: 'Project A' },
        { id: 'p2', name: 'Project B' },
      ],
    };
    const wrapper = mount(DependencyForm);
    const options = wrapper.find('[data-testid="dependency-form-project-id"]').findAll('option');
    // disabled placeholder + 2 projects
    expect(options).toHaveLength(3);
  });

  it('does not show error by default', () => {
    const wrapper = mount(DependencyForm);
    expect(wrapper.find('[data-testid="dependency-form-error"]').exists()).toBe(false);
  });

  it('calls store.create on form submit', async () => {
    mockCreate.mockResolvedValueOnce({ id: 'dep-new' });
    projectStoreOverrides = {
      projects: [{ id: 'p1', name: 'Project A' }],
    };
    const wrapper = mount(DependencyForm);

    await wrapper.find('[data-testid="dependency-form-name"]').setValue('lodash');
    await wrapper.find('[data-testid="dependency-form-current-version"]').setValue('4.17.0');
    await wrapper.find('[data-testid="dependency-form-latest-version"]').setValue('4.17.21');
    await wrapper.find('[data-testid="dependency-form-lts-version"]').setValue('4.17.21');
    await wrapper.find('[data-testid="dependency-form-project-id"]').setValue('p1');
    await wrapper.find('[data-testid="dependency-form"]').trigger('submit');
    await vi.dynamicImportSettled();

    expect(mockCreate).toHaveBeenCalledWith(
      expect.objectContaining({
        name: 'lodash',
        currentVersion: '4.17.0',
        latestVersion: '4.17.21',
      }),
    );
  });

  it('shows error when submission fails', async () => {
    mockCreate.mockRejectedValueOnce(new Error('fail'));
    const wrapper = mount(DependencyForm);

    await wrapper.find('[data-testid="dependency-form-name"]').setValue('lodash');
    await wrapper.find('[data-testid="dependency-form"]').trigger('submit');
    await vi.dynamicImportSettled();

    expect(wrapper.find('[data-testid="dependency-form-error"]').exists()).toBe(true);
  });
});
