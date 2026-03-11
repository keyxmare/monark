import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useDashboardStore } from '@/activity/stores/dashboard'

vi.mock('@/activity/services/dashboard.service', () => ({
  dashboardService: {
    getDashboard: vi.fn(),
  },
}))

import { dashboardService } from '@/activity/services/dashboard.service'

describe('Dashboard Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('loads dashboard metrics', async () => {
    vi.mocked(dashboardService.getDashboard).mockResolvedValue({
      data: {
        metrics: [
          { label: 'Total Events', value: 5 },
          { label: 'Notifications', value: 10 },
          { label: 'Unread', value: 3 },
        ],
      },
      status: 200,
    })

    const store = useDashboardStore()
    await store.load()

    expect(store.metrics).toHaveLength(3)
    expect(store.metrics[0].label).toBe('Total Events')
    expect(store.metrics[0].value).toBe(5)
    expect(store.loading).toBe(false)
  })

  it('sets error on load failure', async () => {
    vi.mocked(dashboardService.getDashboard).mockRejectedValue(new Error('Network error'))

    const store = useDashboardStore()
    await store.load()

    expect(store.error).toBe('Failed to load dashboard')
    expect(store.loading).toBe(false)
  })
})
