<script setup lang="ts">
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useUserStore } from '@/identity/stores/user'

const route = useRoute()
const { t, d } = useI18n()
const userStore = useUserStore()

onMounted(() => {
  const id = route.params.id as string
  userStore.fetchOne(id)
})
</script>

<template>
  <DashboardLayout>
    <div data-testid="user-detail-page">
      <div class="mb-6">
        <RouterLink
          :to="{ name: 'identity-users-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="user-detail-back"
        >
          &larr; {{ t('common.backTo', { page: t('identity.users.title').toLowerCase() }) }}
        </RouterLink>
      </div>

      <div
        v-if="userStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="user-detail-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="userStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="user-detail-error"
      >
        {{ userStore.error }}
      </div>

      <div
        v-else-if="userStore.selectedUser"
        class="max-w-2xl rounded-xl border border-border bg-surface p-6"
        data-testid="user-detail-card"
      >
        <h2 class="mb-6 text-2xl font-bold text-text">
          {{ userStore.selectedUser.firstName }} {{ userStore.selectedUser.lastName }}
        </h2>

        <dl class="space-y-4">
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('identity.users.email') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="user-detail-email"
            >
              {{ userStore.selectedUser.email }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('identity.users.firstName') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="user-detail-first-name"
            >
              {{ userStore.selectedUser.firstName }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('identity.users.lastName') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="user-detail-last-name"
            >
              {{ userStore.selectedUser.lastName }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('identity.users.avatar') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="user-detail-avatar"
            >
              {{ userStore.selectedUser.avatar ?? t('common.notSet') }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('identity.users.roles') }}
            </dt>
            <dd
              class="mt-1 flex flex-wrap gap-2"
              data-testid="user-detail-roles"
            >
              <span
                v-for="role in userStore.selectedUser.roles"
                :key="role"
                class="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary"
              >
                {{ role }}
              </span>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('identity.users.createdAt') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="user-detail-created-at"
            >
              {{ d(new Date(userStore.selectedUser.createdAt), 'short') }}
            </dd>
          </div>
        </dl>
      </div>
    </div>
  </DashboardLayout>
</template>
