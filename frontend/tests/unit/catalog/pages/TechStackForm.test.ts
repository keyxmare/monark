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
const mockFetchAll = vi.fn();
let techStackStoreOverrides: Record<string, unknown> = {};
let projectStoreOverrides: Record<string, unknown> = {};

vi.mock('@/catalog/stores/tech-stack', () => ({
  useTechStackStore: vi.fn(() => ({
    create: mockCreate,
    error: null,
    loading: false,
    techStacks: [],
    ...techStackStoreOverrides,
  })),
}));

vi.mock('@/catalog/stores/project', () => ({
  useProjectStore: vi.fn(() => ({
    fetchAll: mockFetchAll,
    loading: false,
    projects: [],
    ...projectStoreOverrides,
  })),
}));

import TechStackForm from '@/catalog/pages/TechStackForm.vue';

describe('TechStackForm', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    techStackStoreOverrides = {};
    projectStoreOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(TechStackForm);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="tech-stack-form-page"]').exists()).toBe(true);
  });

  it('renders the back link', () => {
    const wrapper = mount(TechStackForm);
    expect(wrapper.find('[data-testid="tech-stack-form-back"]').exists()).toBe(true);
  });

  it('renders the form with all fields', () => {
    const wrapper = mount(TechStackForm);
    expect(wrapper.find('[data-testid="tech-stack-form"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="tech-stack-form-project"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="tech-stack-form-language"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="tech-stack-form-framework"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="tech-stack-form-version"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="tech-stack-form-detected-at"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="tech-stack-form-submit"]').exists()).toBe(true);
  });

  it('fetches projects on mount', () => {
    mount(TechStackForm);
    expect(mockFetchAll).toHaveBeenCalledWith(1, 100);
  });

  it('renders project options when projects exist', () => {
    projectStoreOverrides = {
      projects: [
        { id: 'p1', name: 'Project A' },
        { id: 'p2', name: 'Project B' },
      ],
    };
    const wrapper = mount(TechStackForm);
    const options = wrapper.find('[data-testid="tech-stack-form-project"]').findAll('option');
    // first option is the disabled placeholder
    expect(options.length).toBe(3);
  });

  it('calls store.create on form submit', async () => {
    mockCreate.mockResolvedValueOnce({});
    projectStoreOverrides = {
      projects: [{ id: 'p1', name: 'Project A' }],
    };
    const wrapper = mount(TechStackForm);

    await wrapper.find('[data-testid="tech-stack-form-project"]').setValue('p1');
    await wrapper.find('[data-testid="tech-stack-form-language"]').setValue('TypeScript');
    await wrapper.find('[data-testid="tech-stack-form-framework"]').setValue('Vue');
    await wrapper.find('[data-testid="tech-stack-form-version"]').setValue('5.0');
    await wrapper.find('[data-testid="tech-stack-form"]').trigger('submit');
    await vi.dynamicImportSettled();

    expect(mockCreate).toHaveBeenCalledWith(
      expect.objectContaining({
        language: 'TypeScript',
        framework: 'Vue',
        version: '5.0',
        projectId: 'p1',
      }),
    );
  });

  it('shows error when submission fails', async () => {
    mockCreate.mockRejectedValueOnce(new Error('fail'));
    const wrapper = mount(TechStackForm);

    await wrapper.find('[data-testid="tech-stack-form-language"]').setValue('TS');
    await wrapper.find('[data-testid="tech-stack-form-framework"]').setValue('Vue');
    await wrapper.find('[data-testid="tech-stack-form-version"]').setValue('1.0');
    await wrapper.find('[data-testid="tech-stack-form"]').trigger('submit');
    await vi.dynamicImportSettled();

    expect(wrapper.find('[data-testid="tech-stack-form-error"]').exists()).toBe(true);
  });

  it('does not show error by default', () => {
    const wrapper = mount(TechStackForm);
    expect(wrapper.find('[data-testid="tech-stack-form-error"]').exists()).toBe(false);
  });
});
