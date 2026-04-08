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
    t: (key: string) => key,
  }),
}));

vi.mock('@/shared/layouts/DashboardLayout.vue', () => ({
  default: { template: '<div><slot /></div>' },
}));

vi.mock('@/catalog/components/ProviderIcon.vue', () => ({
  default: { props: ['type', 'size'], template: '<span />' },
}));

const mockCreate = vi.fn();
const mockUpdate = vi.fn();
const mockFetchOne = vi.fn();
const mockTestConnection = vi.fn();
let providerStoreOverrides: Record<string, unknown> = {};

vi.mock('@/catalog/stores/provider', () => ({
  useProviderStore: vi.fn(() => ({
    create: mockCreate,
    error: null,
    fetchOne: mockFetchOne,
    loading: false,
    selected: null,
    testConnection: mockTestConnection,
    update: mockUpdate,
    ...providerStoreOverrides,
  })),
}));

const mockAddToast = vi.fn();
vi.mock('@/shared/stores/toast', () => ({
  useToastStore: vi.fn(() => ({
    addToast: mockAddToast,
  })),
}));

import { useRoute } from 'vue-router';

import ProviderForm from '@/catalog/pages/ProviderForm.vue';

describe('ProviderForm', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    providerStoreOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(ProviderForm);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="provider-form-page"]').exists()).toBe(true);
  });

  it('renders the form with all fields', () => {
    const wrapper = mount(ProviderForm);
    expect(wrapper.find('[data-testid="provider-form"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="field-name"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="field-type"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="field-url"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="field-apiToken"]').exists()).toBe(true);
  });

  it('renders submit and cancel buttons', () => {
    const wrapper = mount(ProviderForm);
    expect(wrapper.find('[data-testid="provider-form-submit"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="provider-form-cancel"]').exists()).toBe(true);
  });

  it('renders back link', () => {
    const wrapper = mount(ProviderForm);
    expect(wrapper.find('[data-testid="provider-form-back"]').exists()).toBe(true);
  });

  it('shows username field when type is github', async () => {
    const wrapper = mount(ProviderForm);
    // Default type is github
    expect(wrapper.find('[data-testid="field-username"]').exists()).toBe(true);
  });

  it('hides username field when type is not github', async () => {
    const wrapper = mount(ProviderForm);
    await wrapper.find('[data-testid="field-type"]').setValue('gitlab');
    expect(wrapper.find('[data-testid="field-username"]').exists()).toBe(false);
  });

  it('toggles token visibility', async () => {
    const wrapper = mount(ProviderForm);
    const input = wrapper.find('[data-testid="field-apiToken"]');
    expect(input.attributes('type')).toBe('password');

    await wrapper.find('[data-testid="toggle-token-visibility"]').trigger('click');
    expect(input.attributes('type')).toBe('text');

    await wrapper.find('[data-testid="toggle-token-visibility"]').trigger('click');
    expect(input.attributes('type')).toBe('password');
  });

  it('shows validation errors on submit with empty fields', async () => {
    const wrapper = mount(ProviderForm);
    // Change to gitlab so apiToken is required
    await wrapper.find('[data-testid="field-type"]').setValue('gitlab');
    await wrapper.find('[data-testid="provider-form"]').trigger('submit');
    await vi.dynamicImportSettled();

    expect(wrapper.find('[data-testid="error-name"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="error-url"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="error-apiToken"]').exists()).toBe(true);
  });

  it('calls create on form submit for new provider', async () => {
    mockCreate.mockResolvedValueOnce({ id: 'new-1' });
    const wrapper = mount(ProviderForm);

    await wrapper.find('[data-testid="field-name"]').setValue('My GitLab');
    await wrapper.find('[data-testid="field-type"]').setValue('gitlab');
    await wrapper.find('[data-testid="field-url"]').setValue('https://gitlab.example.com');
    await wrapper.find('[data-testid="field-apiToken"]').setValue('token123');
    await wrapper.find('[data-testid="provider-form"]').trigger('submit');
    await vi.dynamicImportSettled();

    expect(mockCreate).toHaveBeenCalledWith({
      apiToken: 'token123',
      name: 'My GitLab',
      type: 'gitlab',
      url: 'https://gitlab.example.com',
      username: undefined,
    });
  });

  it('shows form error on create failure', async () => {
    mockCreate.mockRejectedValueOnce(new Error('fail'));
    const wrapper = mount(ProviderForm);

    await wrapper.find('[data-testid="field-name"]').setValue('Test');
    await wrapper.find('[data-testid="field-type"]').setValue('gitlab');
    await wrapper.find('[data-testid="field-url"]').setValue('https://gitlab.example.com');
    await wrapper.find('[data-testid="field-apiToken"]').setValue('tok');
    await wrapper.find('[data-testid="provider-form"]').trigger('submit');
    await vi.dynamicImportSettled();

    expect(wrapper.find('[data-testid="provider-form-error"]').exists()).toBe(true);
  });

  it('does not show loading by default', () => {
    const wrapper = mount(ProviderForm);
    expect(wrapper.find('[data-testid="provider-form-loading"]').exists()).toBe(false);
  });

  it('does not show sidebar when not editing', () => {
    const wrapper = mount(ProviderForm);
    expect(wrapper.find('[data-testid="provider-sidebar-info"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="provider-form-test-connection"]').exists()).toBe(false);
  });
});

describe('ProviderForm (edit mode)', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    providerStoreOverrides = {};

    vi.mocked(useRoute).mockReturnValue({ params: { id: 'prov-1' }, query: {} } as any);
  });

  it('fetches provider on mount in edit mode', () => {
    providerStoreOverrides = {
      selected: {
        createdAt: '2025-01-01T00:00:00+00:00',
        id: 'prov-1',
        lastSyncAt: null,
        name: 'My Provider',
        projectsCount: 3,
        status: 'connected',
        type: 'gitlab',
        url: 'https://gitlab.example.com',
        username: null,
      },
    };
    mount(ProviderForm);
    expect(mockFetchOne).toHaveBeenCalledWith('prov-1');
  });

  it('shows sidebar info in edit mode when provider is selected', () => {
    providerStoreOverrides = {
      selected: {
        createdAt: '2025-01-01T00:00:00+00:00',
        id: 'prov-1',
        lastSyncAt: '2025-06-01T00:00:00+00:00',
        name: 'My Provider',
        projectsCount: 3,
        status: 'connected',
        type: 'gitlab',
        url: 'https://gitlab.example.com',
        username: null,
      },
    };
    const wrapper = mount(ProviderForm);
    expect(wrapper.find('[data-testid="provider-sidebar-info"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="provider-form-test-connection"]').exists()).toBe(true);
  });

  it('shows sidebar status badge', () => {
    providerStoreOverrides = {
      selected: {
        createdAt: '2025-01-01T00:00:00+00:00',
        id: 'prov-1',
        lastSyncAt: null,
        name: 'My Provider',
        projectsCount: 3,
        status: 'connected',
        type: 'gitlab',
        url: 'https://gitlab.example.com',
        username: null,
      },
    };
    const wrapper = mount(ProviderForm);
    expect(wrapper.find('[data-testid="sidebar-status"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="sidebar-projects"]').text()).toContain('3');
  });

  it('calls testConnection when test button is clicked', async () => {
    mockTestConnection.mockResolvedValueOnce(true);
    providerStoreOverrides = {
      selected: {
        createdAt: '2025-01-01T00:00:00+00:00',
        id: 'prov-1',
        lastSyncAt: null,
        name: 'My Provider',
        projectsCount: 3,
        status: 'connected',
        type: 'gitlab',
        url: 'https://gitlab.example.com',
        username: null,
      },
    };
    const wrapper = mount(ProviderForm);
    await wrapper.find('[data-testid="provider-form-test-connection"]').trigger('click');
    await vi.dynamicImportSettled();

    expect(mockTestConnection).toHaveBeenCalledWith('prov-1');
    expect(mockAddToast).toHaveBeenCalled();
  });
});
