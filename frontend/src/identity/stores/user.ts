import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { UpdateUserInput, User } from '@/identity/types/user'
import { userService } from '@/identity/services/user.service'
import { i18n } from '@/shared/i18n'

export const useUserStore = defineStore('user', () => {
  const t = i18n.global.t
  const users = ref<User[]>([])
  const selectedUser = ref<User | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const totalPages = ref(0)
  const currentPage = ref(1)
  const total = ref(0)

  async function fetchAll(page = 1, perPage = 20): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await userService.list(page, perPage)
      users.value = response.data.items
      totalPages.value = response.data.total_pages
      currentPage.value = response.data.page
      total.value = response.data.total
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.users') })
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await userService.get(id)
      selectedUser.value = response.data
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.users') })
    } finally {
      loading.value = false
    }
  }

  async function update(id: string, data: UpdateUserInput): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await userService.update(id, data)
      selectedUser.value = response.data
      const index = users.value.findIndex(u => u.id === id)
      if (index !== -1) {
        users.value[index] = response.data
      }
    } catch {
      error.value = t('common.errors.failedToUpdate', { entity: t('common.entities.users') })
    } finally {
      loading.value = false
    }
  }

  return {
    users,
    selectedUser,
    loading,
    error,
    totalPages,
    currentPage,
    total,
    fetchAll,
    fetchOne,
    update,
  }
})
