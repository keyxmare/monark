import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

import type { SyncJobProgress } from '@/catalog/services/provider.service'

let mercureOnMessage: ((data: SyncJobProgress) => void) | undefined
let mercureClose: ReturnType<typeof vi.fn>

vi.mock('@/shared/composables/useMercure', () => ({
  useMercure: vi.fn((_topic: string, options: { onMessage?: (data: SyncJobProgress) => void } = {}) => {
    mercureOnMessage = options.onMessage
    mercureClose = vi.fn()
    return {
      data: { value: null },
      connected: { value: false },
      close: mercureClose,
    }
  }),
}))

vi.mock('vue-i18n', () => ({
  useI18n: () => ({
    t: (key: string, params?: Record<string, unknown>) => `${key}${params ? JSON.stringify(params) : ''}`,
  }),
}))

import { useSyncProgress } from '@/catalog/composables/useSyncProgress'
import { useToastStore } from '@/shared/stores/toast'

describe('useSyncProgress', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    mercureOnMessage = undefined
  })

  it('creates a progress toast and subscribes to mercure', () => {
    const store = useToastStore()
    const { track } = useSyncProgress()

    track('sync-1', 5)

    expect(store.toasts).toHaveLength(1)
    expect(store.toasts[0].variant).toBe('progress')
    expect(mercureOnMessage).toBeDefined()
  })

  it('updates progress on running status', () => {
    const store = useToastStore()
    const { track } = useSyncProgress()

    track('sync-1', 5)
    const toastId = store.toasts[0].id

    mercureOnMessage?.({
      id: 'sync-1',
      totalProjects: 5,
      completedProjects: 3,
      status: 'running',
      createdAt: '2026-01-01',
      completedAt: null,
    })

    expect(store.toasts[0].progress?.current).toBe(3)
    expect(mercureClose).not.toHaveBeenCalled()
  })

  it('marks toast as success on completed and closes mercure', () => {
    const store = useToastStore()
    const { track } = useSyncProgress()

    track('sync-1', 5)

    mercureOnMessage?.({
      id: 'sync-1',
      totalProjects: 5,
      completedProjects: 5,
      status: 'completed',
      createdAt: '2026-01-01',
      completedAt: '2026-01-01',
    })

    expect(store.toasts[0].variant).toBe('success')
    expect(mercureClose).toHaveBeenCalled()
  })

  it('marks toast as error on failed and closes mercure', () => {
    const store = useToastStore()
    const { track } = useSyncProgress()

    track('sync-1', 5)

    mercureOnMessage?.({
      id: 'sync-1',
      totalProjects: 5,
      completedProjects: 2,
      status: 'failed',
      createdAt: '2026-01-01',
      completedAt: null,
    })

    expect(store.toasts[0].variant).toBe('error')
    expect(mercureClose).toHaveBeenCalled()
  })
})
