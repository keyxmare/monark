import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { CreateQuestionInput, Question, UpdateQuestionInput } from '@/assessment/types/question'
import { questionService } from '@/assessment/services/question.service'

export const useQuestionStore = defineStore('assessment-question', () => {
  const questions = ref<Question[]>([])
  const selectedQuestion = ref<Question | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const totalPages = ref(0)
  const currentPage = ref(1)
  const total = ref(0)

  async function fetchAll(page = 1, perPage = 20, quizId?: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await questionService.list(page, perPage, quizId)
      questions.value = response.data.items
      totalPages.value = response.data.total_pages
      currentPage.value = response.data.page
      total.value = response.data.total
    } catch {
      error.value = 'Failed to load questions'
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await questionService.get(id)
      selectedQuestion.value = response.data
    } catch {
      error.value = 'Failed to load question'
    } finally {
      loading.value = false
    }
  }

  async function create(data: CreateQuestionInput): Promise<Question> {
    loading.value = true
    error.value = null

    try {
      const response = await questionService.create(data)
      questions.value.unshift(response.data)
      return response.data
    } catch {
      error.value = 'Failed to create question'
      throw new Error('Failed to create question')
    } finally {
      loading.value = false
    }
  }

  async function update(id: string, data: UpdateQuestionInput): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await questionService.update(id, data)
      selectedQuestion.value = response.data
      const index = questions.value.findIndex(q => q.id === id)
      if (index !== -1) {
        questions.value[index] = response.data
      }
    } catch {
      error.value = 'Failed to update question'
    } finally {
      loading.value = false
    }
  }

  async function remove(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      await questionService.remove(id)
      questions.value = questions.value.filter(q => q.id !== id)
    } catch {
      error.value = 'Failed to delete question'
    } finally {
      loading.value = false
    }
  }

  return {
    questions,
    selectedQuestion,
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
