<script setup lang="ts">
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute } from 'vue-router'

import { useNotificationStore } from '@/activity/stores/notification'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'

const route = useRoute()
const { d, t } = useI18n()
const notificationStore = useNotificationStore()

onMounted(() => {
  const id = route.params.id as string
  notificationStore.fetchOne(id)
})

async function handleMarkAsRead() {
  if (notificationStore.selectedNotification) {
    await notificationStore.markAsRead(notificationStore.selectedNotification.id)
  }
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="notification-detail-page">
      <div class="mb-6">
        <RouterLink
          :to="{ name: 'activity-notifications-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="notification-detail-back"
        >
          &larr; {{ t('common.backTo', { page: t('activity.notifications.title').toLowerCase() }) }}
        </RouterLink>
      </div>

      <div
        v-if="notificationStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="notification-detail-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="notificationStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="notification-detail-error"
      >
        {{ notificationStore.error }}
      </div>

      <div
        v-else-if="notificationStore.selectedNotification"
        class="max-w-2xl rounded-xl border border-border bg-surface p-6"
        data-testid="notification-detail-card"
      >
        <div class="mb-6 flex items-center justify-between">
          <h2 class="text-2xl font-bold text-text">
            {{ notificationStore.selectedNotification.title }}
          </h2>
          <button
            v-if="!notificationStore.selectedNotification.readAt"
            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
            data-testid="notification-detail-mark-read"
            @click="handleMarkAsRead"
          >
            {{ t('activity.notifications.markAsRead') }}
          </button>
        </div>

        <dl class="space-y-4">
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('activity.notifications.channel') }}
            </dt>
            <dd
              class="mt-1"
              data-testid="notification-detail-channel"
            >
              <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                {{ notificationStore.selectedNotification.channel }}
              </span>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('activity.notifications.status') }}
            </dt>
            <dd
              class="mt-1"
              data-testid="notification-detail-status"
            >
              <span
                :class="[
                  'rounded-full px-3 py-1 text-xs font-medium',
                  notificationStore.selectedNotification.readAt
                    ? 'bg-success/10 text-success'
                    : 'bg-warning/10 text-warning',
                ]"
              >
                {{ notificationStore.selectedNotification.readAt ? t('activity.notifications.read') : t('activity.notifications.unread') }}
              </span>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('activity.notifications.message') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="notification-detail-message"
            >
              {{ notificationStore.selectedNotification.message }}
            </dd>
          </div>
          <div v-if="notificationStore.selectedNotification.readAt">
            <dt class="text-sm font-medium text-text-muted">
              {{ t('activity.notifications.readAt') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="notification-detail-read-at"
            >
              {{ d(new Date(notificationStore.selectedNotification.readAt), 'long') }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('common.createdAt') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="notification-detail-created-at"
            >
              {{ d(new Date(notificationStore.selectedNotification.createdAt), 'long') }}
            </dd>
          </div>
        </dl>
      </div>
    </div>
  </DashboardLayout>
</template>
