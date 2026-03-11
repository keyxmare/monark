import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useQuestionStore } from '@/assessment/stores/question'

vi.mock('@/assessment/services/question.service', () => ({
  questionService: {
    list: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
  },
}))

import { questionService } from '@/assessment/services/question.service'

const mockQuestion = {
  id: '456',
  type: 'single_choice' as const,
  content: 'What is PHP?',
  level: 'easy' as const,
  score: 1,
  position: 1,
  quizId: '123',
  answerCount: 4,
  createdAt: '2026-01-01T00:00:00+00:00',
  updatedAt: '2026-01-01T00:00:00+00:00',
}

describe('Question Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetches all questions', async () => {
    vi.mocked(questionService.list).mockResolvedValue({
      data: {
        items: [mockQuestion],
        total: 1,
        page: 1,
        per_page: 20,
        total_pages: 1,
      },
      status: 200,
    })

    const store = useQuestionStore()
    await store.fetchAll()

    expect(store.questions).toHaveLength(1)
    expect(store.questions[0].content).toBe('What is PHP?')
  })

  it('fetches a single question', async () => {
    vi.mocked(questionService.get).mockResolvedValue({
      data: mockQuestion,
      status: 200,
    })

    const store = useQuestionStore()
    await store.fetchOne('456')

    expect(store.selectedQuestion).toEqual(mockQuestion)
  })

  it('creates a question', async () => {
    vi.mocked(questionService.create).mockResolvedValue({
      data: mockQuestion,
      status: 201,
    })

    const store = useQuestionStore()
    const result = await store.create({
      type: 'single_choice',
      content: 'What is PHP?',
      level: 'easy',
      score: 1,
      position: 1,
      quizId: '123',
    })

    expect(result.id).toBe('456')
    expect(store.questions).toHaveLength(1)
  })

  it('updates a question', async () => {
    const updatedQuestion = { ...mockQuestion, content: 'What is PHP 8.4?' }
    vi.mocked(questionService.update).mockResolvedValue({
      data: updatedQuestion,
      status: 200,
    })

    const store = useQuestionStore()
    store.questions = [mockQuestion]
    await store.update('456', { content: 'What is PHP 8.4?' })

    expect(store.selectedQuestion?.content).toBe('What is PHP 8.4?')
    expect(store.questions[0].content).toBe('What is PHP 8.4?')
  })

  it('removes a question', async () => {
    vi.mocked(questionService.remove).mockResolvedValue(undefined)

    const store = useQuestionStore()
    store.questions = [mockQuestion]

    await store.remove('456')

    expect(store.questions).toHaveLength(0)
  })

  it('sets error on fetch failure', async () => {
    vi.mocked(questionService.list).mockRejectedValue(new Error('Network error'))

    const store = useQuestionStore()
    await store.fetchAll()

    expect(store.error).toBe('Failed to load questions')
  })
})
