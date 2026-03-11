<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useNotificationStore } from '@/activity/stores/notification'

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
          Notifications
        </h2>
      </div>

      <div
        v-if="notificationStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="notification-list-loading"
      >
        Loading...
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
                Title
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Channel
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Status
              </th>
              <th class="px-4 py-3 text-right text-sm font-medium text-text-muted">
                Actions
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
                  {{ notification.readAt ? 'Read' : 'Unread' }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <button
                  v-if="!notification.readAt"
                  class="text-sm text-primary hover:text-primary-dark"
                  data-testid="notification-mark-read"
                  @click="handleMarkAsRead(notification.id)"
                >
                  Mark as read
                </button>
                <RouterLink
                  :to="{ name: 'activity-notifications-detail', params: { id: notification.id } }"
                  class="ml-3 text-sm text-primary hover:text-primary-dark"
                  data-testid="notification-view-link"
                >
                  View
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
          No notifications found.
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
