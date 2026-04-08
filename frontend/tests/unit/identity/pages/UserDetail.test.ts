import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
  useRoute: vi.fn(() => ({ params: { id: 'user-1' }, query: {} })),
  useRouter: vi.fn(() => ({ push: vi.fn() })),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({
    d: (date: Date) => date.toISOString(),
    t: (key: string, params?: Record<string, string>) => key,
  }),
}));

vi.mock('@/shared/layouts/DashboardLayout.vue', () => ({
  default: { template: '<div><slot /></div>' },
}));

const mockFetchOne = vi.fn();
let storeOverrides: Record<string, unknown> = {};

vi.mock('@/identity/stores/user', () => ({
  useUserStore: vi.fn(() => ({
    error: null,
    fetchOne: mockFetchOne,
    loading: false,
    selectedUser: null,
    users: [],
    ...storeOverrides,
  })),
}));

import UserDetail from '@/identity/pages/UserDetail.vue';

describe('UserDetail', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    storeOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(UserDetail);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="user-detail-page"]').exists()).toBe(true);
  });

  it('calls fetchOne on mount with route param id', () => {
    mount(UserDetail);
    expect(mockFetchOne).toHaveBeenCalledWith('user-1');
  });

  it('renders the back link', () => {
    const wrapper = mount(UserDetail);
    expect(wrapper.find('[data-testid="user-detail-back"]').exists()).toBe(true);
  });

  it('shows loading state', () => {
    storeOverrides = { loading: true };
    const wrapper = mount(UserDetail);
    expect(wrapper.find('[data-testid="user-detail-loading"]').exists()).toBe(true);
  });

  it('shows error state', () => {
    storeOverrides = { error: 'User not found' };
    const wrapper = mount(UserDetail);
    expect(wrapper.find('[data-testid="user-detail-error"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="user-detail-error"]').text()).toContain('User not found');
  });

  it('renders user detail card when user exists', () => {
    storeOverrides = {
      selectedUser: {
        id: 'user-1',
        email: 'john@example.com',
        firstName: 'John',
        lastName: 'Doe',
        avatar: null,
        roles: ['ROLE_USER', 'ROLE_ADMIN'],
        createdAt: '2025-01-01T00:00:00+00:00',
        updatedAt: '2025-01-01T00:00:00+00:00',
      },
    };
    const wrapper = mount(UserDetail);
    expect(wrapper.find('[data-testid="user-detail-card"]').exists()).toBe(true);
  });

  it('displays user fields', () => {
    storeOverrides = {
      selectedUser: {
        id: 'user-1',
        email: 'john@example.com',
        firstName: 'John',
        lastName: 'Doe',
        avatar: 'https://example.com/avatar.png',
        roles: ['ROLE_USER'],
        createdAt: '2025-01-01T00:00:00+00:00',
        updatedAt: '2025-01-01T00:00:00+00:00',
      },
    };
    const wrapper = mount(UserDetail);
    expect(wrapper.find('[data-testid="user-detail-email"]').text()).toContain('john@example.com');
    expect(wrapper.find('[data-testid="user-detail-first-name"]').text()).toContain('John');
    expect(wrapper.find('[data-testid="user-detail-last-name"]').text()).toContain('Doe');
    expect(wrapper.find('[data-testid="user-detail-avatar"]').text()).toContain(
      'https://example.com/avatar.png',
    );
    expect(wrapper.find('[data-testid="user-detail-roles"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="user-detail-created-at"]').exists()).toBe(true);
  });

  it('does not show user card when no user selected', () => {
    const wrapper = mount(UserDetail);
    expect(wrapper.find('[data-testid="user-detail-card"]').exists()).toBe(false);
  });
});
