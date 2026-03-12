<script setup lang="ts">
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'

import { useNotificationStore } from '@/activity/stores/notification'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'

const { t } = useI18n()
const notificationStore = useNotificationStore()

onMounted(() => {
  notificationStore.fetchAll()
})

async function handleMarkAsRead(id: string) {
  await notificationStore.markAsRead(id)
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="notification-list-page">
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-text">
          {{ t('activity.notifications.title') }}
        </h2>
      </div>

      <div
        v-if="notificationStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="notification-list-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="notificationStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="notification-list-error"
      >
        {{ notificationStore.error }}
      </div>

      <div
        v-else
        class="overflow-hidden rounded-xl border border-border bg-surface"
      >
        <table
          class="w-full"
          data-testid="notification-list-table"
        >
          <thead>
            <tr class="border-b border-border bg-surface-muted">
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('activity.notifications.notifTitle') }}
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('activity.notifications.channel') }}
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('activity.notifications.status') }}
              </th>
              <th class="px-4 py-3 text-right text-sm font-medium text-text-muted">
                {{ t('common.table.actions') }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="notification in notificationStore.notifications"
              :key="notification.id"
              class="border-b border-border last:border-0"
              data-testid="notification-list-row"
            >
              <td class="px-4 py-3 text-sm text-text">
                <RouterLink
                  :to="{ name: 'activity-notifications-detail', params: { id: notification.id } }"
                  class="hover:text-primary"
                  data-testid="notification-title-link"
                >
                  {{ notification.title }}
                </RouterLink>
              </td>
              <td class="px-4 py-3 text-sm text-text">
                <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                  {{ notification.channel }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm">
                <span
                  :class="[
                    'rounded-full px-3 py-1 text-xs font-medium',
                    notification.readAt
                      ? 'bg-success/10 text-success'
                      : 'bg-warning/10 text-warning',
                  ]"
                  data-testid="notification-status-badge"
                >
                  {{ notification.readAt ? t('activity.notifications.read') : t('activity.notifications.unread') }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <button
                  v-if="!notification.readAt"
                  class="text-sm text-primary hover:text-primary-dark"
                  data-testid="notification-mark-read"
                  @click="handleMarkAsRead(notification.id)"
                >
                  {{ t('activity.notifications.markAsRead') }}
                </button>
                <RouterLink
                  :to="{ name: 'activity-notifications-detail', params: { id: notification.id } }"
                  class="ml-3 text-sm text-primary hover:text-primary-dark"
                  data-testid="notification-view-link"
                >
                  {{ t('common.actions.view') }}
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>

        <div
          v-if="notificationStore.notifications.length === 0"
          class="py-8 text-center text-text-muted"
          data-testid="notification-list-empty"
        >
          {{ t('activity.notifications.noNotifications') }}
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
