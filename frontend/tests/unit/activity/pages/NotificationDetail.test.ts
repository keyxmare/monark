import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
  useRoute: vi.fn(() => ({ params: { id: 'n1' }, query: {} })),
  useRouter: vi.fn(() => ({ push: vi.fn() })),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({
    d: (v: Date) => v.toISOString(),
    t: (key: string, params?: Record<string, string>) => key,
  }),
}));

vi.mock('@/shared/layouts/DashboardLayout.vue', () => ({
  default: { template: '<div><slot /></div>' },
}));

const mockFetchOne = vi.fn();
const mockMarkAsRead = vi.fn();
let storeOverrides: Record<string, unknown> = {};

vi.mock('@/activity/stores/notification', () => ({
  useNotificationStore: vi.fn(() => ({
    selectedNotification: null,
    error: null,
    fetchOne: mockFetchOne,
    markAsRead: mockMarkAsRead,
    loading: false,
    ...storeOverrides,
  })),
}));

import NotificationDetail from '@/activity/pages/NotificationDetail.vue';

describe('NotificationDetail', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    storeOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(NotificationDetail);
    expect(wrapper.find('[data-testid="notification-detail-page"]').exists()).toBe(true);
  });

  it('calls fetchOne on mount', () => {
    mount(NotificationDetail);
    expect(mockFetchOne).toHaveBeenCalledWith('n1');
  });

  it('shows loading state', () => {
    storeOverrides = { loading: true };
    const wrapper = mount(NotificationDetail);
    expect(wrapper.find('[data-testid="notification-detail-loading"]').exists()).toBe(true);
  });

  it('shows error state', () => {
    storeOverrides = { error: 'Not found' };
    const wrapper = mount(NotificationDetail);
    expect(wrapper.find('[data-testid="notification-detail-error"]').exists()).toBe(true);
  });

  it('renders notification details when loaded', () => {
    storeOverrides = {
      selectedNotification: {
        id: 'n1',
        title: 'Test Notification',
        message: 'A message body',
        channel: 'in_app',
        readAt: null,
        userId: 'u1',
        createdAt: '2025-01-01T00:00:00+00:00',
      },
    };
    const wrapper = mount(NotificationDetail);
    expect(wrapper.find('[data-testid="notification-detail-card"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="notification-detail-channel"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="notification-detail-status"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="notification-detail-message"]').exists()).toBe(true);
  });

  it('shows mark as read button for unread notification', () => {
    storeOverrides = {
      selectedNotification: {
        id: 'n1',
        title: 'Test',
        message: 'Msg',
        channel: 'in_app',
        readAt: null,
        userId: 'u1',
        createdAt: '2025-01-01T00:00:00+00:00',
      },
    };
    const wrapper = mount(NotificationDetail);
    expect(wrapper.find('[data-testid="notification-detail-mark-read"]').exists()).toBe(true);
  });

  it('hides mark as read button for read notification', () => {
    storeOverrides = {
      selectedNotification: {
        id: 'n1',
        title: 'Test',
        message: 'Msg',
        channel: 'in_app',
        readAt: '2025-01-02T00:00:00+00:00',
        userId: 'u1',
        createdAt: '2025-01-01T00:00:00+00:00',
      },
    };
    const wrapper = mount(NotificationDetail);
    expect(wrapper.find('[data-testid="notification-detail-mark-read"]').exists()).toBe(false);
  });

  it('renders back link', () => {
    const wrapper = mount(NotificationDetail);
    expect(wrapper.find('[data-testid="notification-detail-back"]').exists()).toBe(true);
  });
});
