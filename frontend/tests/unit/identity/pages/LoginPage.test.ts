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

vi.mock('@/shared/layouts/AuthLayout.vue', () => ({
  default: { template: '<div><slot /></div>' },
}));

const mockLogin = vi.fn();
vi.mock('@/identity/stores/auth', () => ({
  useAuthStore: vi.fn(() => ({
    currentUser: null,
    error: null,
    isAuthenticated: false,
    loading: false,
    login: mockLogin,
    register: vi.fn(),
    token: null,
  })),
}));

import LoginPage from '@/identity/pages/LoginPage.vue';

describe('LoginPage', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('renders without errors', () => {
    const wrapper = mount(LoginPage);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="login-page"]').exists()).toBe(true);
  });

  it('renders the login form', () => {
    const wrapper = mount(LoginPage);
    expect(wrapper.find('[data-testid="login-form"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="login-email"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="login-password"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="login-submit"]').exists()).toBe(true);
  });

  it('renders the register link', () => {
    const wrapper = mount(LoginPage);
    expect(wrapper.find('[data-testid="login-register-link"]').exists()).toBe(true);
  });

  it('does not show error by default', () => {
    const wrapper = mount(LoginPage);
    expect(wrapper.find('[data-testid="login-error"]').exists()).toBe(false);
  });

  it('shows error after failed login', async () => {
    mockLogin.mockRejectedValueOnce(new Error('fail'));
    const wrapper = mount(LoginPage);

    await wrapper.find('[data-testid="login-email"]').setValue('test@test.com');
    await wrapper.find('[data-testid="login-password"]').setValue('wrong');
    await wrapper.find('[data-testid="login-form"]').trigger('submit');
    await vi.dynamicImportSettled();

    expect(wrapper.find('[data-testid="login-error"]').exists()).toBe(true);
  });

  it('calls login on form submit', async () => {
    mockLogin.mockResolvedValueOnce(undefined);
    const wrapper = mount(LoginPage);

    await wrapper.find('[data-testid="login-email"]').setValue('user@example.com');
    await wrapper.find('[data-testid="login-password"]').setValue('password123');
    await wrapper.find('[data-testid="login-form"]').trigger('submit');
    await vi.dynamicImportSettled();

    expect(mockLogin).toHaveBeenCalledWith('user@example.com', 'password123');
  });
});
