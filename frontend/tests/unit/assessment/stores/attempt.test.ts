import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useAttemptStore } from '@/assessment/stores/attempt'

vi.mock('@/assessment/services/attempt.service', () => ({
  attemptService: {
    list: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
  },
}))

import { attemptService } from '@/assessment/services/attempt.service'

const mockAttempt = {
  id: '999',
  score: 0,
  status: 'started' as const,
  startedAt: '2026-01-01T00:00:00+00:00',
  finishedAt: null,
  userId: '00000000-0000-0000-0000-000000000001',
  quizId: '00000000-0000-0000-0000-000000000002',
  createdAt: '2026-01-01T00:00:00+00:00',
}

describe('Attempt Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetches all attempts', async () => {
    vi.mocked(attemptService.list).mockResolvedValue({
      data: {
        items: [mockAttempt],
        total: 1,
        page: 1,
        per_page: 20,
        total_pages: 1,
      },
      status: 200,
    })

    const store = useAttemptStore()
    await store.fetchAll()

    expect(store.attempts).toHaveLength(1)
    expect(store.attempts[0].status).toBe('started')
  })

  it('fetches a single attempt', async () => {
    vi.mocked(attemptService.get).mockResolvedValue({
      data: mockAttempt,
      status: 200,
    })

    const store = useAttemptStore()
    await store.fetchOne('999')

    expect(store.selectedAttempt).toEqual(mockAttempt)
  })

  it('creates an attempt', async () => {
    vi.mocked(attemptService.create).mockResolvedValue({
      data: mockAttempt,
      status: 201,
    })

    const store = useAttemptStore()
    const result = await store.create({
      userId: '00000000-0000-0000-0000-000000000001',
      quizId: '00000000-0000-0000-0000-000000000002',
    })

    expect(result.id).toBe('999')
    expect(store.attempts).toHaveLength(1)
  })

  it('sets error on fetch failure', async () => {
    vi.mocked(attemptService.list).mockRejectedValue(new Error('Network error'))

    const store = useAttemptStore()
    await store.fetchAll()

    expect(store.error).toBe('Failed to load attempts')
  })
})
