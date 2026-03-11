import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useQuizStore } from '@/assessment/stores/quiz'

vi.mock('@/assessment/services/quiz.service', () => ({
  quizService: {
    list: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
  },
}))

import { quizService } from '@/assessment/services/quiz.service'

const mockQuiz = {
  id: '123',
  title: 'PHP Fundamentals',
  slug: 'php-fundamentals',
  description: 'A quiz about PHP basics',
  type: 'quiz' as const,
  status: 'draft' as const,
  startsAt: null,
  endsAt: null,
  timeLimit: null,
  authorId: '456',
  questionCount: 5,
  createdAt: '2026-01-01T00:00:00+00:00',
  updatedAt: '2026-01-01T00:00:00+00:00',
}

describe('Quiz Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetches all quizzes', async () => {
    vi.mocked(quizService.list).mockResolvedValue({
      data: {
        items: [mockQuiz],
        total: 1,
        page: 1,
        per_page: 20,
        total_pages: 1,
      },
      status: 200,
    })

    const store = useQuizStore()
    await store.fetchAll()

    expect(store.quizzes).toHaveLength(1)
    expect(store.quizzes[0].title).toBe('PHP Fundamentals')
  })

  it('fetches a single quiz', async () => {
    vi.mocked(quizService.get).mockResolvedValue({
      data: mockQuiz,
      status: 200,
    })

    const store = useQuizStore()
    await store.fetchOne('123')

    expect(store.selectedQuiz).toEqual(mockQuiz)
  })

  it('creates a quiz', async () => {
    vi.mocked(quizService.create).mockResolvedValue({
      data: mockQuiz,
      status: 201,
    })

    const store = useQuizStore()
    const result = await store.create({
      title: 'PHP Fundamentals',
      slug: 'php-fundamentals',
      description: 'A quiz about PHP basics',
      type: 'quiz',
    })

    expect(result.id).toBe('123')
    expect(store.quizzes).toHaveLength(1)
  })

  it('updates a quiz', async () => {
    const updatedQuiz = { ...mockQuiz, title: 'Advanced PHP' }
    vi.mocked(quizService.update).mockResolvedValue({
      data: updatedQuiz,
      status: 200,
    })

    const store = useQuizStore()
    store.quizzes = [mockQuiz]
    await store.update('123', { title: 'Advanced PHP' })

    expect(store.selectedQuiz?.title).toBe('Advanced PHP')
    expect(store.quizzes[0].title).toBe('Advanced PHP')
  })

  it('removes a quiz', async () => {
    vi.mocked(quizService.remove).mockResolvedValue(undefined)

    const store = useQuizStore()
    store.quizzes = [mockQuiz]

    await store.remove('123')

    expect(store.quizzes).toHaveLength(0)
  })

  it('sets error on fetch failure', async () => {
    vi.mocked(quizService.list).mockRejectedValue(new Error('Network error'))

    const store = useQuizStore()
    await store.fetchAll()

    expect(store.error).toBe('Failed to load quizzes')
  })
})
