import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { AccessToken, CreateAccessTokenInput } from '@/identity/types/access-token'
import { accessTokenService } from '@/identity/services/access-token.service'

export const useAccessTokenStore = defineStore('accessToken', () => {
  const tokens = ref<AccessToken[]>([])
  const selectedToken = ref<AccessToken | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const totalPages = ref(0)
  const currentPage = ref(1)
  const total = ref(0)

  async function fetchAll(page = 1, perPage = 20): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await accessTokenService.list(page, perPage)
      tokens.value = response.data.items
      totalPages.value = response.data.total_pages
      currentPage.value = response.data.page
      total.value = response.data.total
    } catch {
      error.value = 'Failed to load access tokens'
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await accessTokenService.get(id)
      selectedToken.value = response.data
    } catch {
      error.value = 'Failed to load access token'
    } finally {
      loading.value = false
    }
  }

  async function create(data: CreateAccessTokenInput): Promise<AccessToken> {
    loading.value = true
    error.value = null

    try {
      const response = await accessTokenService.create(data)
      tokens.value.unshift(response.data)
      return response.data
    } catch {
      error.value = 'Failed to create access token'
      throw new Error('Failed to create access token')
    } finally {
      loading.value = false
    }
  }

  async function remove(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      await accessTokenService.remove(id)
      tokens.value = tokens.value.filter(t => t.id !== id)
    } catch {
      error.value = 'Failed to delete access token'
    } finally {
      loading.value = false
    }
  }

  return {
    tokens,
    selectedToken,
    loading,
    error,
    totalPages,
    currentPage,
    total,
    fetchAll,
    fetchOne,
    create,
    remove,
  }
})
