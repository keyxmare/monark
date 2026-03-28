<script setup lang="ts">
import { onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';

import { useUserStore } from '@/identity/stores/user';
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue';

const { t } = useI18n();
const userStore = useUserStore();

onMounted(() => {
  userStore.fetchAll();
});
</script>

<template>
  <DashboardLayout>
    <div data-testid="user-list-page">
      <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text">
          {{ t('identity.users.title') }}
        </h2>
      </div>

      <div
        v-if="userStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="user-list-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="userStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="user-list-error"
      >
        {{ userStore.error }}
      </div>

      <div v-else class="overflow-hidden rounded-xl border border-border bg-surface">
        <table class="w-full" data-testid="user-list-table">
          <thead>
            <tr class="border-b border-border bg-surface-muted">
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('identity.users.email') }}
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('identity.users.firstName') }}
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('identity.users.lastName') }}
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('identity.users.roles') }}
              </th>
              <th class="px-4 py-3 text-right text-sm font-medium text-text-muted">
                {{ t('common.table.actions') }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="user in userStore.users"
              :key="user.id"
              class="border-b border-border last:border-0"
              data-testid="user-list-row"
            >
              <td class="px-4 py-3 text-sm text-text">
                {{ user.email }}
              </td>
              <td class="px-4 py-3 text-sm text-text">
                {{ user.firstName }}
              </td>
              <td class="px-4 py-3 text-sm text-text">
                {{ user.lastName }}
              </td>
              <td class="px-4 py-3 text-sm text-text">
                {{ user.roles.length }}
              </td>
              <td class="px-4 py-3 text-right">
                <RouterLink
                  :to="{ name: 'identity-users-detail', params: { id: user.id } }"
                  class="text-sm text-primary hover:text-primary-dark"
                  data-testid="user-list-view"
                >
                  {{ t('common.actions.view') }}
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>

        <div
          v-if="userStore.users.length === 0"
          class="py-8 text-center text-text-muted"
          data-testid="user-list-empty"
        >
          {{ t('identity.users.noUsers') }}
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
