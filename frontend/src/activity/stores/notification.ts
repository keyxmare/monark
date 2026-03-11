import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { Notification } from '@/activity/types/notification'
import { notificationService } from '@/activity/services/notification.service'
import { i18n } from '@/shared/i18n'

export const useNotificationStore = defineStore('notification', () => {
  const t = i18n.global.t
  const notifications = ref<Notification[]>([])
  const selectedNotification = ref<Notification | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const totalPages = ref(0)
  const currentPage = ref(1)
  const total = ref(0)

  async function fetchAll(page = 1, perPage = 20): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await notificationService.list(page, perPage)
      notifications.value = response.data.items
      totalPages.value = response.data.total_pages
      currentPage.value = response.data.page
      total.value = response.data.total
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.notifications') })
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await notificationService.get(id)
      selectedNotification.value = response.data
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.notifications') })
    } finally {
      loading.value = false
    }
  }

  async function markAsRead(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await notificationService.markAsRead(id)
      const index = notifications.value.findIndex(n => n.id === id)
      if (index !== -1) {
        notifications.value[index] = response.data
      }
      if (selectedNotification.value?.id === id) {
        selectedNotification.value = response.data
      }
    } catch {
      error.value = t('common.errors.failedToMarkAsRead')
    } finally {
      loading.value = false
    }
  }

  return {
    currentPage,
    error,
    fetchAll,
    fetchOne,
    loading,
    markAsRead,
    notifications,
    selectedNotification,
    total,
    totalPages,
  }
})
