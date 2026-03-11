import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useAnswerStore } from '@/assessment/stores/answer'

vi.mock('@/assessment/services/answer.service', () => ({
  answerService: {
    list: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
  },
}))

import { answerService } from '@/assessment/services/answer.service'

const mockAnswer = {
  id: '789',
  content: 'A programming language',
  isCorrect: true,
  position: 1,
  questionId: '456',
  createdAt: '2026-01-01T00:00:00+00:00',
  updatedAt: '2026-01-01T00:00:00+00:00',
}

describe('Answer Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetches all answers', async () => {
    vi.mocked(answerService.list).mockResolvedValue({
      data: {
        items: [mockAnswer],
        total: 1,
        page: 1,
        per_page: 20,
        total_pages: 1,
      },
      status: 200,
    })

    const store = useAnswerStore()
    await store.fetchAll()

    expect(store.answers).toHaveLength(1)
    expect(store.answers[0].content).toBe('A programming language')
  })

  it('fetches a single answer', async () => {
    vi.mocked(answerService.get).mockResolvedValue({
      data: mockAnswer,
      status: 200,
    })

    const store = useAnswerStore()
    await store.fetchOne('789')

    expect(store.selectedAnswer).toEqual(mockAnswer)
  })

  it('creates an answer', async () => {
    vi.mocked(answerService.create).mockResolvedValue({
      data: mockAnswer,
      status: 201,
    })

    const store = useAnswerStore()
    const result = await store.create({
      content: 'A programming language',
      isCorrect: true,
      position: 1,
      questionId: '456',
    })

    expect(result.id).toBe('789')
    expect(store.answers).toHaveLength(1)
  })

  it('updates an answer', async () => {
    const updatedAnswer = { ...mockAnswer, content: 'A scripting language' }
    vi.mocked(answerService.update).mockResolvedValue({
      data: updatedAnswer,
      status: 200,
    })

    const store = useAnswerStore()
    store.answers = [mockAnswer]
    await store.update('789', { content: 'A scripting language' })

    expect(store.selectedAnswer?.content).toBe('A scripting language')
    expect(store.answers[0].content).toBe('A scripting language')
  })

  it('removes an answer', async () => {
    vi.mocked(answerService.remove).mockResolvedValue(undefined)

    const store = useAnswerStore()
    store.answers = [mockAnswer]

    await store.remove('789')

    expect(store.answers).toHaveLength(0)
  })

  it('sets error on fetch failure', async () => {
    vi.mocked(answerService.list).mockRejectedValue(new Error('Network error'))

    const store = useAnswerStore()
    await store.fetchAll()

    expect(store.error).toBe('Failed to load answers')
  })
})
