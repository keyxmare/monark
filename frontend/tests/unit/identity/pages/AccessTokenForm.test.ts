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
let storeOverrides: Record<string, unknown> = {};

vi.mock('@/identity/stores/access-token', () => ({
  useAccessTokenStore: vi.fn(() => ({
    create: mockCreate,
    error: null,
    loading: false,
    tokens: [],
    ...storeOverrides,
  })),
}));

import AccessTokenForm from '@/identity/pages/AccessTokenForm.vue';

describe('AccessTokenForm', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    storeOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(AccessTokenForm);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="access-token-form-page"]').exists()).toBe(true);
  });

  it('renders the back link', () => {
    const wrapper = mount(AccessTokenForm);
    expect(wrapper.find('[data-testid="access-token-form-back"]').exists()).toBe(true);
  });

  it('renders the form with all fields', () => {
    const wrapper = mount(AccessTokenForm);
    expect(wrapper.find('[data-testid="access-token-form"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="access-token-provider"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="access-token-token"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="access-token-scopes"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="access-token-expires-at"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="access-token-submit"]').exists()).toBe(true);
  });

  it('does not show error by default', () => {
    const wrapper = mount(AccessTokenForm);
    expect(wrapper.find('[data-testid="access-token-form-error"]').exists()).toBe(false);
  });

  it('calls store.create on form submit', async () => {
    mockCreate.mockResolvedValueOnce({});
    const wrapper = mount(AccessTokenForm);

    await wrapper.find('[data-testid="access-token-provider"]').setValue('github');
    await wrapper.find('[data-testid="access-token-token"]').setValue('ghp_abc123');
    await wrapper.find('[data-testid="access-token-scopes"]').setValue('repo, read:org');
    await wrapper.find('[data-testid="access-token-form"]').trigger('submit');
    await vi.dynamicImportSettled();

    expect(mockCreate).toHaveBeenCalledWith(
      expect.objectContaining({
        provider: 'github',
        token: 'ghp_abc123',
        scopes: ['repo', 'read:org'],
      }),
    );
  });

  it('shows error when submission fails', async () => {
    mockCreate.mockRejectedValueOnce(new Error('fail'));
    const wrapper = mount(AccessTokenForm);

    await wrapper.find('[data-testid="access-token-token"]').setValue('bad-token');
    await wrapper.find('[data-testid="access-token-form"]').trigger('submit');
    await vi.dynamicImportSettled();

    expect(wrapper.find('[data-testid="access-token-form-error"]').exists()).toBe(true);
  });

  it('renders provider options', () => {
    const wrapper = mount(AccessTokenForm);
    const options = wrapper.find('[data-testid="access-token-provider"]').findAll('option');
    expect(options.length).toBe(2);
  });
});
