<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRouter } from 'vue-router'

import type { Provider } from '@/catalog/types/provider'

import ProviderIcon from '@/catalog/components/ProviderIcon.vue'
import { useSyncProgress } from '@/catalog/composables/useSyncProgress'
import { useProviderStore } from '@/catalog/stores/provider'
import ConfirmDialog from '@/shared/components/ConfirmDialog.vue'
import DropdownMenu from '@/shared/components/DropdownMenu.vue'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useToastStore } from '@/shared/stores/toast'

const router = useRouter()
const { t, d } = useI18n()
const providerStore = useProviderStore()
const toastStore = useToastStore()
const { track } = useSyncProgress()
const testingId = ref<null | string>(null)
const syncing = ref(false)
const deleteTarget = ref<null | { id: string; name: string }>(null)

onMounted(() => {
  providerStore.fetchAll()
})

async function confirmDelete() {
  if (!deleteTarget.value) return
  await providerStore.remove(deleteTarget.value.id)
  deleteTarget.value = null
}

function getDropdownItems(provider: Provider) {
  return [
    { action: 'test', disabled: testingId.value === provider.id, label: testingId.value === provider.id ? t('catalog.providers.testing') : t('catalog.providers.test') },
    { action: 'view', label: t('common.actions.view') },
    { action: 'edit', label: t('common.actions.edit') },
    { action: 'delete', label: t('common.actions.delete'), variant: 'danger' as const },
  ]
}

function handleDropdownAction(action: string, provider: Provider) {
  if (action === 'test') handleTestConnection(provider)
  else if (action === 'view') router.push({ name: 'catalog-providers-detail', params: { id: provider.id } })
  else if (action === 'edit') router.push({ name: 'catalog-providers-edit', params: { id: provider.id } })
  else if (action === 'delete') requestDelete(provider)
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

async function handleTestConnection(provider: { id: string; name: string }) {
  testingId.value = provider.id
  const connected = await providerStore.testConnection(provider.id)
  testingId.value = null
  toastStore.addToast({
    title: connected
      ? t('catalog.providers.connectionSuccess', { name: provider.name })
      : t('catalog.providers.connectionFailed', { name: provider.name }),
    variant: connected ? 'success' : 'error',
  })
}

function navigateToDetail(id: string) {
  router.push({ name: 'catalog-providers-detail', params: { id } })
}

function requestDelete(provider: { id: string; name: string }) {
  deleteTarget.value = provider
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
        class="rounded-xl border border-border bg-surface"
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
              <th class="px-4 py-3 text-right text-sm font-medium text-text-muted">
                {{ t('catalog.providers.projects') }}
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
              class="cursor-pointer border-b border-border last:border-0 hover:bg-background/50"
              data-testid="provider-list-row"
              @click="navigateToDetail(provider.id)"
            >
              <td class="px-4 py-3 text-sm font-medium text-text">
                {{ provider.name }}
              </td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex items-center gap-1.5 text-sm"
                  data-testid="provider-type-badge"
                >
                  <ProviderIcon
                    :type="provider.type"
                    :size="16"
                  />
                  {{ t(`catalog.providers.types.${provider.type}`) }}
                </span>
              </td>
              <td
                class="px-4 py-3 text-sm"
                @click.stop
              >
                <a
                  :href="provider.url"
                  class="text-primary hover:underline"
                  data-testid="provider-url-link"
                  rel="noopener"
                  target="_blank"
                >
                  {{ provider.url }}
                </a>
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
                  {{ t(`catalog.providers.statuses.${provider.status}`) }}
                </span>
              </td>
              <td
                class="px-4 py-3 text-right text-sm tabular-nums text-text"
                data-testid="provider-projects-count"
              >
                {{ provider.projectsCount }}
              </td>
              <td class="px-4 py-3 text-sm text-text-muted">
                {{ provider.lastSyncAt ? d(new Date(provider.lastSyncAt), 'short') : '—' }}
              </td>
              <td
                class="px-4 py-3 text-right"
                @click.stop
              >
                <DropdownMenu
                  :items="getDropdownItems(provider)"
                  @select="handleDropdownAction($event, provider)"
                />
              </td>
            </tr>
          </tbody>
        </table>

        <div
          v-if="providerStore.providers.length === 0"
          class="flex flex-col items-center py-12"
          data-testid="provider-list-empty"
        >
          <svg
            class="mb-4 h-12 w-12 text-text-muted/50"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.374a4.5 4.5 0 00-1.242-7.244l4.5-4.5a4.5 4.5 0 016.364 6.364l-1.757 1.757"
            />
          </svg>
          <p class="mb-1 text-sm font-medium text-text">
            {{ t('catalog.providers.noProviders') }}
          </p>
          <p class="mb-4 text-sm text-text-muted">
            {{ t('catalog.providers.noProvidersHint') }}
          </p>
          <RouterLink
            :to="{ name: 'catalog-providers-create' }"
            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
            data-testid="provider-empty-create-link"
          >
            {{ t('catalog.providers.createProvider') }}
          </RouterLink>
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
