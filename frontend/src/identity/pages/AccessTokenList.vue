<script setup lang="ts">
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useAccessTokenStore } from '@/identity/stores/access-token'

const { t } = useI18n()
const tokenStore = useAccessTokenStore()

onMounted(() => {
  tokenStore.fetchAll()
})

async function handleDelete(id: string) {
  await tokenStore.remove(id)
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="access-token-list-page">
      <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text">
          {{ t('identity.accessTokens.title') }}
        </h2>
        <RouterLink
          :to="{ name: 'identity-access-tokens-create' }"
          class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
          data-testid="access-token-create-link"
        >
          {{ t('identity.accessTokens.addToken') }}
        </RouterLink>
      </div>

      <div
        v-if="tokenStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="access-token-list-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="tokenStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="access-token-list-error"
      >
        {{ tokenStore.error }}
      </div>

      <div
        v-else
        class="overflow-hidden rounded-xl border border-border bg-surface"
      >
        <table
          class="w-full"
          data-testid="access-token-list-table"
        >
          <thead>
            <tr class="border-b border-border bg-surface-muted">
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('identity.accessTokens.provider') }}
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('identity.accessTokens.scopes') }}
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('identity.accessTokens.expiresAt') }}
              </th>
              <th class="px-4 py-3 text-right text-sm font-medium text-text-muted">
                {{ t('common.table.actions') }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="token in tokenStore.tokens"
              :key="token.id"
              class="border-b border-border last:border-0"
              data-testid="access-token-list-row"
            >
              <td class="px-4 py-3 text-sm text-text">
                <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary capitalize">
                  {{ token.provider }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-text">
                {{ token.scopes.join(', ') || t('common.none') }}
              </td>
              <td class="px-4 py-3 text-sm text-text">
                {{ token.expiresAt ? new Date(token.expiresAt).toLocaleDateString() : t('common.never') }}
              </td>
              <td class="px-4 py-3 text-right">
                <button
                  class="text-sm text-danger hover:text-danger/80"
                  data-testid="access-token-delete"
                  @click="handleDelete(token.id)"
                >
                  {{ t('common.actions.delete') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div
          v-if="tokenStore.tokens.length === 0"
          class="py-8 text-center text-text-muted"
          data-testid="access-token-list-empty"
        >
          {{ t('identity.accessTokens.noTokens') }}
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
