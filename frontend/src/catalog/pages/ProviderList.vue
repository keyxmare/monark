<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRouter } from 'vue-router'

import type { Provider } from '@/catalog/types/provider'

import ProviderCard from '@/catalog/components/ProviderCard.vue'
import { useSyncProgress } from '@/catalog/composables/useSyncProgress'
import { useProviderStore } from '@/catalog/stores/provider'
import ConfirmDialog from '@/shared/components/ConfirmDialog.vue'
import { useConfirmDelete } from '@/shared/composables/useConfirmDelete'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useToastStore } from '@/shared/stores/toast'

const router = useRouter()
const { t } = useI18n()
const providerStore = useProviderStore()
const toastStore = useToastStore()
const { track } = useSyncProgress()
const testingId = ref<null | string>(null)
const syncing = ref(false)
const search = ref('')
const typeFilter = ref('')
const statusFilter = ref('')
const { target: deleteTarget, isOpen: deleteOpen, requestDelete, cancel: cancelDelete, confirm: confirmDelete } = useConfirmDelete<{ id: string; name: string }>()

const filteredProviders = computed(() => {
  return providerStore.providers.filter((p) => {
    if (search.value && !p.name.toLowerCase().includes(search.value.toLowerCase())) return false
    if (typeFilter.value && p.type !== typeFilter.value) return false
    if (statusFilter.value && p.status !== statusFilter.value) return false
    return true
  })
})

const hasActiveFilters = computed(() => search.value !== '' || typeFilter.value !== '' || statusFilter.value !== '')

onMounted(() => {
  providerStore.fetchAll()
})

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
</script>

<template>
  <DashboardLayout>
    <div data-testid="provider-list-page">
      <nav
        class="mb-6 flex items-center gap-1 text-sm text-text-muted"
        data-testid="provider-list-breadcrumb"
      >
        <span class="font-medium text-text">
          {{ t('catalog.providers.title') }}
        </span>
      </nav>

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
        v-if="providerStore.loading && providerStore.providers.length === 0"
        class="py-8 text-center text-text-muted"
        data-testid="provider-list-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <template v-else>
        <div
          v-if="providerStore.error"
          class="mb-4 rounded-lg border border-warning/30 bg-warning/10 p-3 text-sm text-warning"
          role="alert"
          data-testid="provider-list-error"
        >
          {{ providerStore.error }}
        </div>
        <div
          v-if="providerStore.providers.length > 0"
          class="mb-4 flex flex-wrap items-center gap-3"
          data-testid="provider-list-filters"
        >
          <div class="relative flex-1">
            <svg
              class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted"
              fill="none"
              stroke="currentColor"
              stroke-width="1.5"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
              />
            </svg>
            <input
              v-model="search"
              type="search"
              :aria-label="t('catalog.providers.searchProviders')"
              :placeholder="t('catalog.providers.searchProviders')"
              class="w-full rounded-lg border border-border bg-surface py-2 pl-9 pr-3 text-sm text-text placeholder:text-text-muted focus:border-primary focus:outline-none"
              data-testid="provider-search"
            >
          </div>
          <select
            v-model="typeFilter"
            :aria-label="t('catalog.providers.allTypes')"
            class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"
            data-testid="provider-filter-type"
          >
            <option value="">
              {{ t('catalog.providers.allTypes') }}
            </option>
            <option value="gitlab">
              {{ t('catalog.providers.types.gitlab') }}
            </option>
            <option value="github">
              {{ t('catalog.providers.types.github') }}
            </option>
            <option value="bitbucket">
              {{ t('catalog.providers.types.bitbucket') }}
            </option>
          </select>
          <select
            v-model="statusFilter"
            :aria-label="t('catalog.providers.allStatuses')"
            class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"
            data-testid="provider-filter-status"
          >
            <option value="">
              {{ t('catalog.providers.allStatuses') }}
            </option>
            <option value="connected">
              {{ t('catalog.providers.statuses.connected') }}
            </option>
            <option value="pending">
              {{ t('catalog.providers.statuses.pending') }}
            </option>
            <option value="error">
              {{ t('catalog.providers.statuses.error') }}
            </option>
          </select>
        </div>

        <div
          v-if="filteredProviders.length > 0"
          class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
          data-testid="provider-list-grid"
        >
          <ProviderCard
            v-for="provider in filteredProviders"
            :key="provider.id"
            :provider="provider"
            :items="getDropdownItems(provider)"
            @navigate="navigateToDetail(provider.id)"
            @dropdown-action="handleDropdownAction($event, provider)"
          />
        </div>

        <div
          v-if="hasActiveFilters && filteredProviders.length === 0"
          class="flex flex-col items-center rounded-xl border border-border bg-surface py-12"
          data-testid="provider-list-no-match"
        >
          <p class="text-sm text-text-muted">
            {{ t('catalog.providers.noMatchingProviders') }}
          </p>
        </div>

        <div
          v-if="providerStore.providers.length === 0"
          class="flex flex-col items-center rounded-xl border border-border bg-surface py-12"
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
      </template>
      <ConfirmDialog
        :open="deleteOpen"
        :title="t('catalog.providers.confirmDeleteTitle')"
        :message="t('catalog.providers.confirmDeleteMessage', { name: deleteTarget?.name ?? '' })"
        :confirm-label="t('common.actions.delete')"
        variant="danger"
        @confirm="confirmDelete(() => providerStore.remove(deleteTarget!.id))"
        @cancel="cancelDelete"
      />
    </div>
  </DashboardLayout>
</template>
