import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { CreateQuizInput, Quiz, UpdateQuizInput } from '@/assessment/types/quiz'
import { quizService } from '@/assessment/services/quiz.service'

export const useQuizStore = defineStore('assessment-quiz', () => {
  const quizzes = ref<Quiz[]>([])
  const selectedQuiz = ref<Quiz | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const totalPages = ref(0)
  const currentPage = ref(1)
  const total = ref(0)

  async function fetchAll(page = 1, perPage = 20): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await quizService.list(page, perPage)
      quizzes.value = response.data.items
      totalPages.value = response.data.total_pages
      currentPage.value = response.data.page
      total.value = response.data.total
    } catch {
      error.value = 'Failed to load quizzes'
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await quizService.get(id)
      selectedQuiz.value = response.data
    } catch {
      error.value = 'Failed to load quiz'
    } finally {
      loading.value = false
    }
  }

  async function create(data: CreateQuizInput): Promise<Quiz> {
    loading.value = true
    error.value = null

    try {
      const response = await quizService.create(data)
      quizzes.value.unshift(response.data)
      return response.data
    } catch {
      error.value = 'Failed to create quiz'
      throw new Error('Failed to create quiz')
    } finally {
      loading.value = false
    }
  }

  async function update(id: string, data: UpdateQuizInput): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await quizService.update(id, data)
      selectedQuiz.value = response.data
      const index = quizzes.value.findIndex(q => q.id === id)
      if (index !== -1) {
        quizzes.value[index] = response.data
      }
    } catch {
      error.value = 'Failed to update quiz'
    } finally {
      loading.value = false
    }
  }

  async function remove(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      await quizService.remove(id)
      quizzes.value = quizzes.value.filter(q => q.id !== id)
    } catch {
      error.value = 'Failed to delete quiz'
    } finally {
      loading.value = false
    }
  }

  return {
    quizzes,
    selectedQuiz,
    loading,
    error,
    totalPages,
    currentPage,
    total,
    fetchAll,
    fetchOne,
    create,
    update,
    remove,
  }
})
