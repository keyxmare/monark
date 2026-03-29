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
const mockMarkAsRead = vi.fn();
let storeOverrides: Record<string, unknown> = {};

vi.mock('@/activity/stores/notification', () => ({
  useNotificationStore: vi.fn(() => ({
    notifications: [],
    error: null,
    fetchAll: mockFetchAll,
    markAsRead: mockMarkAsRead,
    loading: false,
    ...storeOverrides,
  })),
}));

import NotificationList from '@/activity/pages/NotificationList.vue';

describe('NotificationList', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    storeOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(NotificationList);
    expect(wrapper.find('[data-testid="notification-list-page"]').exists()).toBe(true);
  });

  it('calls fetchAll on mount', () => {
    mount(NotificationList);
    expect(mockFetchAll).toHaveBeenCalled();
  });

  it('shows loading state', () => {
    storeOverrides = { loading: true };
    const wrapper = mount(NotificationList);
    expect(wrapper.find('[data-testid="notification-list-loading"]').exists()).toBe(true);
  });

  it('shows error state', () => {
    storeOverrides = { error: 'Failed' };
    const wrapper = mount(NotificationList);
    expect(wrapper.find('[data-testid="notification-list-error"]').exists()).toBe(true);
  });

  it('shows empty state when no notifications', () => {
    const wrapper = mount(NotificationList);
    expect(wrapper.find('[data-testid="notification-list-empty"]').exists()).toBe(true);
  });

  it('renders notification rows', () => {
    storeOverrides = {
      notifications: [
        {
          id: 'n1',
          title: 'Test Notification',
          message: 'A message',
          channel: 'in_app',
          readAt: null,
          userId: 'u1',
          createdAt: '2025-01-01T00:00:00+00:00',
        },
      ],
    };
    const wrapper = mount(NotificationList);
    expect(wrapper.findAll('[data-testid="notification-list-row"]')).toHaveLength(1);
  });

  it('shows mark as read button for unread notifications', () => {
    storeOverrides = {
      notifications: [
        {
          id: 'n1',
          title: 'Test',
          message: 'Msg',
          channel: 'in_app',
          readAt: null,
          userId: 'u1',
          createdAt: '2025-01-01T00:00:00+00:00',
        },
      ],
    };
    const wrapper = mount(NotificationList);
    expect(wrapper.find('[data-testid="notification-mark-read"]').exists()).toBe(true);
  });

  it('calls markAsRead when button is clicked', async () => {
    storeOverrides = {
      notifications: [
        {
          id: 'n1',
          title: 'Test',
          message: 'Msg',
          channel: 'in_app',
          readAt: null,
          userId: 'u1',
          createdAt: '2025-01-01T00:00:00+00:00',
        },
      ],
    };
    const wrapper = mount(NotificationList);
    await wrapper.find('[data-testid="notification-mark-read"]').trigger('click');
    expect(mockMarkAsRead).toHaveBeenCalledWith('n1');
  });
});
