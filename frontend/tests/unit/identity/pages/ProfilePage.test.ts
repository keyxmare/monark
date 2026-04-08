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

const mockFetchCurrentUser = vi.fn();
let storeOverrides: Record<string, unknown> = {};

vi.mock('@/identity/stores/auth', () => ({
  useAuthStore: vi.fn(() => ({
    currentUser: null,
    error: null,
    fetchCurrentUser: mockFetchCurrentUser,
    isAuthenticated: false,
    loading: false,
    token: null,
    ...storeOverrides,
  })),
}));

import ProfilePage from '@/identity/pages/ProfilePage.vue';

describe('ProfilePage', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    storeOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(ProfilePage);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="profile-page"]').exists()).toBe(true);
  });

  it('calls fetchCurrentUser on mount', () => {
    mount(ProfilePage);
    expect(mockFetchCurrentUser).toHaveBeenCalled();
  });

  it('shows loading state', () => {
    storeOverrides = { loading: true };
    const wrapper = mount(ProfilePage);
    expect(wrapper.find('[data-testid="profile-loading"]').exists()).toBe(true);
  });

  it('renders profile card when user exists', () => {
    storeOverrides = {
      currentUser: {
        id: 'user-1',
        email: 'john@example.com',
        firstName: 'John',
        lastName: 'Doe',
        avatar: null,
        roles: ['ROLE_USER'],
        createdAt: '2025-01-01T00:00:00+00:00',
        updatedAt: '2025-01-01T00:00:00+00:00',
      },
    };
    const wrapper = mount(ProfilePage);
    expect(wrapper.find('[data-testid="profile-card"]').exists()).toBe(true);
  });

  it('displays user details', () => {
    storeOverrides = {
      currentUser: {
        id: 'user-1',
        email: 'john@example.com',
        firstName: 'John',
        lastName: 'Doe',
        avatar: null,
        roles: ['ROLE_USER'],
        createdAt: '2025-01-01T00:00:00+00:00',
        updatedAt: '2025-01-01T00:00:00+00:00',
      },
    };
    const wrapper = mount(ProfilePage);
    expect(wrapper.find('[data-testid="profile-first-name"]').text()).toContain('John');
    expect(wrapper.find('[data-testid="profile-last-name"]').text()).toContain('Doe');
    expect(wrapper.find('[data-testid="profile-email"]').text()).toContain('john@example.com');
  });

  it('does not show profile card when no user', () => {
    const wrapper = mount(ProfilePage);
    expect(wrapper.find('[data-testid="profile-card"]').exists()).toBe(false);
  });
});
