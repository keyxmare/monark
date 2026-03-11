import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useNotificationStore } from '@/activity/stores/notification'

vi.mock('@/activity/services/notification.service', () => ({
  notificationService: {
    list: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
    markAsRead: vi.fn(),
  },
}))

import { notificationService } from '@/activity/services/notification.service'

const mockNotification = {
  id: '123',
  title: 'Test Alert',
  message: 'Something happened.',
  channel: 'in_app' as const,
  readAt: null,
  userId: '456',
  createdAt: '2026-01-01T00:00:00+00:00',
}

describe('Notification Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetches all notifications', async () => {
    vi.mocked(notificationService.list).mockResolvedValue({
      data: {
        items: [mockNotification],
        total: 1,
        page: 1,
        per_page: 20,
        total_pages: 1,
      },
      status: 200,
    })

    const store = useNotificationStore()
    await store.fetchAll()

    expect(store.notifications).toHaveLength(1)
    expect(store.notifications[0].title).toBe('Test Alert')
  })

  it('fetches one notification', async () => {
    vi.mocked(notificationService.get).mockResolvedValue({
      data: mockNotification,
      status: 200,
    })

    const store = useNotificationStore()
    await store.fetchOne('123')

    expect(store.selectedNotification).not.toBeNull()
    expect(store.selectedNotification!.title).toBe('Test Alert')
  })

  it('marks a notification as read', async () => {
    const readNotification = { ...mockNotification, readAt: '2026-01-02T00:00:00+00:00' }
    vi.mocked(notificationService.markAsRead).mockResolvedValue({
      data: readNotification,
      status: 200,
    })

    const store = useNotificationStore()
    store.notifications = [mockNotification]

    await store.markAsRead('123')

    expect(store.notifications[0].readAt).toBe('2026-01-02T00:00:00+00:00')
  })

  it('sets error on fetch failure', async () => {
    vi.mocked(notificationService.list).mockRejectedValue(new Error('Network error'))

    const store = useNotificationStore()
    await store.fetchAll()

    expect(store.error).toBe('Failed to load notifications')
  })

  it('sets error on mark as read failure', async () => {
    vi.mocked(notificationService.markAsRead).mockRejectedValue(new Error('Server error'))

    const store = useNotificationStore()
    await store.markAsRead('123')

    expect(store.error).toBe('Failed to mark as read')
  })
})
