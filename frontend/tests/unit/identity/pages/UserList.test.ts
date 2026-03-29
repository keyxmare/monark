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

const mockFetchAll = vi.fn();
let storeOverrides: Record<string, unknown> = {};

vi.mock('@/identity/stores/user', () => ({
  useUserStore: vi.fn(() => ({
    currentPage: 1,
    error: null,
    fetchAll: mockFetchAll,
    loading: false,
    selectedUser: null,
    total: 0,
    totalPages: 0,
    users: [],
    ...storeOverrides,
  })),
}));

import UserList from '@/identity/pages/UserList.vue';

describe('UserList', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    storeOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(UserList);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="user-list-page"]').exists()).toBe(true);
  });

  it('calls fetchAll on mount', () => {
    mount(UserList);
    expect(mockFetchAll).toHaveBeenCalled();
  });

  it('shows loading state', () => {
    storeOverrides = { loading: true };
    const wrapper = mount(UserList);
    expect(wrapper.find('[data-testid="user-list-loading"]').exists()).toBe(true);
  });

  it('shows error state', () => {
    storeOverrides = { error: 'Something went wrong' };
    const wrapper = mount(UserList);
    expect(wrapper.find('[data-testid="user-list-error"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="user-list-error"]').text()).toContain('Something went wrong');
  });

  it('shows empty state when no users', () => {
    const wrapper = mount(UserList);
    expect(wrapper.find('[data-testid="user-list-empty"]').exists()).toBe(true);
  });

  it('renders user rows when users exist', () => {
    storeOverrides = {
      users: [
        { id: '1', email: 'a@b.com', firstName: 'John', lastName: 'Doe', roles: ['ROLE_USER'] },
        { id: '2', email: 'c@d.com', firstName: 'Jane', lastName: 'Doe', roles: [] },
      ],
    };
    const wrapper = mount(UserList);
    const rows = wrapper.findAll('[data-testid="user-list-row"]');
    expect(rows).toHaveLength(2);
  });
});
