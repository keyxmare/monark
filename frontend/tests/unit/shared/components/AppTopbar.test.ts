import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const pushMock = vi.fn();
const toggleMobileMock = vi.fn();

vi.mock('vue-router', () => ({
  RouterLink: { template: '<a><slot /></a>' },
  useRoute: vi.fn(() => ({ params: {}, path: '/' })),
  useRouter: vi.fn(() => ({ push: pushMock })),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string) => key }),
}));

vi.mock('@/shared/composables/useSidebar', () => ({
  useSidebar: () => ({
    toggleMobile: toggleMobileMock,
  }),
}));

const logoutMock = vi.fn(() => Promise.resolve());
const currentUserRef = { value: { firstName: 'John', lastName: 'Doe', email: 'john@example.com' } };

vi.mock('@/identity/stores/auth', () => ({
  useAuthStore: () => ({
    currentUser: currentUserRef.value,
    logout: logoutMock,
  }),
}));

vi.mock('@/shared/components/LanguageSwitcher.vue', () => ({
  default: { template: '<div data-testid="language-switcher-stub" />' },
}));

import AppTopbar from '@/shared/components/AppTopbar.vue';

function mountTopbar() {
  return mount(AppTopbar);
}

describe('AppTopbar', () => {
  beforeEach(() => {
    pushMock.mockClear();
    toggleMobileMock.mockClear();
    logoutMock.mockClear();
    currentUserRef.value = { firstName: 'John', lastName: 'Doe', email: 'john@example.com' };
  });

  it('renders the topbar', () => {
    const wrapper = mountTopbar();
    expect(wrapper.find('[data-testid="topbar"]').exists()).toBe(true);
  });

  it('displays app title', () => {
    const wrapper = mountTopbar();
    expect(wrapper.text()).toContain('Monark');
  });

  it('shows user initials', () => {
    const wrapper = mountTopbar();
    expect(wrapper.find('[data-testid="user-avatar"]').text()).toBe('JD');
  });

  it('shows user full name', () => {
    const wrapper = mountTopbar();
    expect(wrapper.text()).toContain('John Doe');
  });

  it('shows ? when no user', () => {
    currentUserRef.value = null as never;
    const wrapper = mountTopbar();
    expect(wrapper.find('[data-testid="user-avatar"]').text()).toBe('?');
  });

  it('calls toggleMobile on menu button click', async () => {
    const wrapper = mountTopbar();
    await wrapper.find('[data-testid="topbar-menu-toggle"]').trigger('click');
    expect(toggleMobileMock).toHaveBeenCalledOnce();
  });

  it('toggles user menu on click', async () => {
    const wrapper = mountTopbar();
    expect(wrapper.find('[data-testid="user-menu"]').exists()).toBe(false);
    await wrapper.find('[data-testid="user-menu-toggle"]').trigger('click');
    expect(wrapper.find('[data-testid="user-menu"]').exists()).toBe(true);
  });

  it('calls logout and redirects on logout click', async () => {
    const wrapper = mountTopbar();
    await wrapper.find('[data-testid="user-menu-toggle"]').trigger('click');
    await wrapper.find('[data-testid="logout-btn"]').trigger('click');
    expect(logoutMock).toHaveBeenCalledOnce();
    expect(pushMock).toHaveBeenCalledWith({ name: 'login' });
  });
});
