import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterView: { template: '<div data-testid="router-view" />' },
}));

vi.mock('@/shared/components/AppToastContainer.vue', () => ({
  default: { template: '<div data-testid="toast-container" />' },
}));

const mockFetchCurrentUser = vi.fn();
let authStoreOverrides: Record<string, unknown> = {};

vi.mock('@/identity/stores/auth', () => ({
  useAuthStore: vi.fn(() => ({
    currentUser: null,
    fetchCurrentUser: mockFetchCurrentUser,
    token: null,
    ...authStoreOverrides,
  })),
}));

import App from '@/app/App.vue';

describe('App', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    authStoreOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(App);
    expect(wrapper.exists()).toBe(true);
  });

  it('renders RouterView and AppToastContainer', () => {
    const wrapper = mount(App);
    expect(wrapper.find('[data-testid="router-view"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="toast-container"]').exists()).toBe(true);
  });

  it('does not call fetchCurrentUser when no token', () => {
    mount(App);
    expect(mockFetchCurrentUser).not.toHaveBeenCalled();
  });

  it('calls fetchCurrentUser when token exists', () => {
    authStoreOverrides = { token: 'my-jwt-token' };
    mount(App);
    expect(mockFetchCurrentUser).toHaveBeenCalled();
  });
});
