import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { Answer, CreateAnswerInput, UpdateAnswerInput } from '@/assessment/types/answer'
import { answerService } from '@/assessment/services/answer.service'

export const useAnswerStore = defineStore('assessment-answer', () => {
  const answers = ref<Answer[]>([])
  const selectedAnswer = ref<Answer | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const totalPages = ref(0)
  const currentPage = ref(1)
  const total = ref(0)

  async function fetchAll(page = 1, perPage = 20, questionId?: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await answerService.list(page, perPage, questionId)
      answers.value = response.data.items
      totalPages.value = response.data.total_pages
      currentPage.value = response.data.page
      total.value = response.data.total
    } catch {
      error.value = 'Failed to load answers'
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await answerService.get(id)
      selectedAnswer.value = response.data
    } catch {
      error.value = 'Failed to load answer'
    } finally {
      loading.value = false
    }
  }

  async function create(data: CreateAnswerInput): Promise<Answer> {
    loading.value = true
    error.value = null

    try {
      const response = await answerService.create(data)
      answers.value.unshift(response.data)
      return response.data
    } catch {
      error.value = 'Failed to create answer'
      throw new Error('Failed to create answer')
    } finally {
      loading.value = false
    }
  }

  async function update(id: string, data: UpdateAnswerInput): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await answerService.update(id, data)
      selectedAnswer.value = response.data
      const index = answers.value.findIndex(a => a.id === id)
      if (index !== -1) {
        answers.value[index] = response.data
      }
    } catch {
      error.value = 'Failed to update answer'
    } finally {
      loading.value = false
    }
  }

  async function remove(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      await answerService.remove(id)
      answers.value = answers.value.filter(a => a.id !== id)
    } catch {
      error.value = 'Failed to delete answer'
    } finally {
      loading.value = false
    }
  }

  return {
    answers,
    selectedAnswer,
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
