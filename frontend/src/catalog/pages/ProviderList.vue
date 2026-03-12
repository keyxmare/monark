<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'

import { useSyncProgress } from '@/catalog/composables/useSyncProgress'
import { useProviderStore } from '@/catalog/stores/provider'
import ConfirmDialog from '@/shared/components/ConfirmDialog.vue'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'

const { t, d } = useI18n()
const providerStore = useProviderStore()
const { track } = useSyncProgress()
const testingId = ref<null | string>(null)
const syncing = ref(false)
const deleteTarget = ref<null | { id: string; name: string }>(null)

onMounted(() => {
  providerStore.fetchAll()
})

function requestDelete(provider: { id: string; name: string }) {
  deleteTarget.value = provider
}

async function confirmDelete() {
  if (!deleteTarget.value) return
  await providerStore.remove(deleteTarget.value.id)
  deleteTarget.value = null
}

async function handleTestConnection(id: string) {
  testingId.value = id
  await providerStore.testConnection(id)
  testingId.value = null
}

async function handleSyncAll() {
  syncing.value = true
  try {
    const result = await providerStore.syncAllGlobal()
    track(result.id, result.projectsCount)
  } catch {
    // error handled by store
  } finally {
    syncing.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="provider-list-page">
      <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text">
          {{ t('catalog.providers.title') }}
        </h2>
        <div class="flex items-center gap-3">
          <button
            :disabled="syncing"
            class="rounded-lg border border-primary bg-transparent px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary hover:text-white disabled:opacity-50"
            data-testid="provider-sync-all-global"
            @click="handleSyncAll"
          >
            {{ syncing ? t('catalog.providers.syncing') : t('catalog.providers.syncAll') }}
          </button>
          <RouterLink
            :to="{ name: 'catalog-providers-create' }"
            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
            data-testid="provider-create-link"
          >
            {{ t('catalog.providers.createProvider') }}
          </RouterLink>
        </div>
      </div>

      <div
        v-if="providerStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="provider-list-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="providerStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="provider-list-error"
      >
        {{ providerStore.error }}
      </div>

      <div
        v-else
        class="overflow-hidden rounded-xl border border-border bg-surface"
      >
        <table
          class="w-full"
          data-testid="provider-list-table"
        >
          <thead>
            <tr class="border-b border-border bg-surface-muted">
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('catalog.providers.name') }}
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('catalog.providers.type') }}
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('catalog.providers.url') }}
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('catalog.providers.status') }}
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('catalog.providers.lastSync') }}
              </th>
              <th class="px-4 py-3 text-right text-sm font-medium text-text-muted">
                {{ t('common.table.actions') }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="provider in providerStore.providers"
              :key="provider.id"
              class="border-b border-border last:border-0"
              data-testid="provider-list-row"
            >
              <td class="px-4 py-3 text-sm text-text">
                {{ provider.name }}
              </td>
              <td class="px-4 py-3">
                <span
                  :class="{
                    'bg-orange-100 text-orange-800': provider.type === 'gitlab',
                    'bg-gray-100 text-gray-800': provider.type === 'github',
                    'bg-blue-100 text-blue-800': provider.type === 'bitbucket',
                  }"
                  class="rounded-full px-2 py-0.5 text-xs font-medium"
                  data-testid="provider-type-badge"
                >
                  {{ provider.type }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-text-muted">
                {{ provider.url }}
              </td>
              <td class="px-4 py-3">
                <span
                  :class="{
                    'bg-green-100 text-green-800': provider.status === 'connected',
                    'bg-yellow-100 text-yellow-800': provider.status === 'pending',
                    'bg-red-100 text-red-800': provider.status === 'error',
                  }"
                  class="rounded-full px-2 py-0.5 text-xs font-medium"
                  data-testid="provider-status-badge"
                >
                  {{ provider.status }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-text-muted">
                {{ provider.lastSyncAt ? d(new Date(provider.lastSyncAt), 'short') : '—' }}
              </td>
              <td class="flex items-center justify-end gap-3 px-4 py-3">
                <button
                  :disabled="testingId === provider.id"
                  class="text-sm text-primary hover:text-primary-dark disabled:opacity-50"
                  data-testid="provider-test-connection"
                  @click="handleTestConnection(provider.id)"
                >
                  {{ testingId === provider.id ? t('catalog.providers.testing') : t('catalog.providers.test') }}
                </button>
                <RouterLink
                  :to="{ name: 'catalog-providers-detail', params: { id: provider.id } }"
                  class="text-sm text-primary hover:text-primary-dark"
                  data-testid="provider-view-link"
                >
                  {{ t('common.actions.view') }}
                </RouterLink>
                <RouterLink
                  :to="{ name: 'catalog-providers-edit', params: { id: provider.id } }"
                  class="text-sm text-primary hover:text-primary-dark"
                  data-testid="provider-edit-link"
                >
                  {{ t('common.actions.edit') }}
                </RouterLink>
                <button
                  class="text-sm text-danger hover:text-danger/80"
                  data-testid="provider-delete"
                  @click="requestDelete(provider)"
                >
                  {{ t('common.actions.delete') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div
          v-if="providerStore.providers.length === 0"
          class="py-8 text-center text-text-muted"
          data-testid="provider-list-empty"
        >
          {{ t('catalog.providers.noProviders') }}
        </div>
      </div>
      <ConfirmDialog
        :open="deleteTarget !== null"
        :title="t('catalog.providers.confirmDeleteTitle')"
        :message="t('catalog.providers.confirmDeleteMessage', { name: deleteTarget?.name ?? '' })"
        :confirm-label="t('common.actions.delete')"
        variant="danger"
        @confirm="confirmDelete"
        @cancel="deleteTarget = null"
      />
    </div>
  </DashboardLayout>
</template>
