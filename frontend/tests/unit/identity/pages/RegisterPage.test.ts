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

const mockRegister = vi.fn();
const mockLogin = vi.fn();
vi.mock('@/identity/stores/auth', () => ({
  useAuthStore: vi.fn(() => ({
    currentUser: null,
    error: null,
    isAuthenticated: false,
    loading: false,
    login: mockLogin,
    register: mockRegister,
    token: null,
  })),
}));

import RegisterPage from '@/identity/pages/RegisterPage.vue';

describe('RegisterPage', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('renders without errors', () => {
    const wrapper = mount(RegisterPage);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="register-page"]').exists()).toBe(true);
  });

  it('renders the registration form fields', () => {
    const wrapper = mount(RegisterPage);
    expect(wrapper.find('[data-testid="register-form"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="register-first-name"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="register-last-name"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="register-email"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="register-password"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="register-submit"]').exists()).toBe(true);
  });

  it('renders the login link', () => {
    const wrapper = mount(RegisterPage);
    expect(wrapper.find('[data-testid="register-login-link"]').exists()).toBe(true);
  });

  it('does not show error by default', () => {
    const wrapper = mount(RegisterPage);
    expect(wrapper.find('[data-testid="register-error"]').exists()).toBe(false);
  });

  it('shows error after failed registration', async () => {
    mockRegister.mockRejectedValueOnce(new Error('fail'));
    const wrapper = mount(RegisterPage);

    await wrapper.find('[data-testid="register-first-name"]').setValue('John');
    await wrapper.find('[data-testid="register-last-name"]').setValue('Doe');
    await wrapper.find('[data-testid="register-email"]').setValue('john@test.com');
    await wrapper.find('[data-testid="register-password"]').setValue('pass123');
    await wrapper.find('[data-testid="register-form"]').trigger('submit');
    await vi.dynamicImportSettled();

    expect(wrapper.find('[data-testid="register-error"]').exists()).toBe(true);
  });

  it('calls register then login on form submit', async () => {
    mockRegister.mockResolvedValueOnce(undefined);
    mockLogin.mockResolvedValueOnce(undefined);
    const wrapper = mount(RegisterPage);

    await wrapper.find('[data-testid="register-first-name"]').setValue('John');
    await wrapper.find('[data-testid="register-last-name"]').setValue('Doe');
    await wrapper.find('[data-testid="register-email"]').setValue('john@test.com');
    await wrapper.find('[data-testid="register-password"]').setValue('pass123');
    await wrapper.find('[data-testid="register-form"]').trigger('submit');
    await vi.dynamicImportSettled();

    expect(mockRegister).toHaveBeenCalledWith('john@test.com', 'pass123', 'John', 'Doe');
    expect(mockLogin).toHaveBeenCalledWith('john@test.com', 'pass123');
  });
});
