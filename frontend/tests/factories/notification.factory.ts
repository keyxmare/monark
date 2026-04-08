import type { Notification } from '@/activity/types/notification';

export function createNotification(overrides?: Partial<Notification>): Notification {
  return {
    id: 'notif-1',
    title: 'New vulnerability detected',
    message: 'A high severity vulnerability was found in vue@3.4.0.',
    channel: 'in_app',
    readAt: null,
    userId: 'user-1',
    createdAt: '2025-01-01T00:00:00+00:00',
    ...overrides,
  };
}
