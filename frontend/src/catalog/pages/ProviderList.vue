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
import { useConfirmDelete } from '@/shared/composables/useConfirmDelete'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useToastStore } from '@/shared/stores/toast'

const router = useRouter()
const { d, t } = useI18n()
const providerStore = useProviderStore()
const toastStore = useToastStore()
const { track } = useSyncProgress()
const testingId = ref<null | string>(null)
const syncing = ref(false)
const { target: deleteTarget, isOpen: deleteOpen, requestDelete, cancel: cancelDelete, confirm: confirmDelete } = useConfirmDelete<{ id: string; name: string }>()

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

      <template v-else>
        <div
          v-if="providerStore.providers.length > 0"
          class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
          data-testid="provider-list-grid"
        >
          <div
            v-for="provider in providerStore.providers"
            :key="provider.id"
            class="cursor-pointer rounded-xl border border-border bg-surface p-5 shadow-sm transition-shadow hover:shadow-md"
            data-testid="provider-list-card"
            role="link"
            tabindex="0"
            @click="navigateToDetail(provider.id)"
            @keydown.enter="navigateToDetail(provider.id)"
          >
            <div class="mb-3 flex items-start justify-between">
              <div class="flex items-center gap-3">
                <ProviderIcon
                  :type="provider.type"
                  :size="24"
                />
                <div>
                  <h3 class="text-sm font-semibold text-text">
                    {{ provider.name }}
                  </h3>
                  <p class="text-xs text-text-muted">
                    {{ t(`catalog.providers.types.${provider.type}`) }}
                  </p>
                </div>
              </div>
              <div
                class="flex items-center gap-2"
                @click.stop
              >
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
                <DropdownMenu
                  :items="getDropdownItems(provider)"
                  @select="handleDropdownAction($event, provider)"
                />
              </div>
            </div>

            <div
              class="mb-3"
              @click.stop
            >
              <a
                :href="provider.url"
                class="truncate text-xs text-primary hover:underline"
                data-testid="provider-url-link"
                rel="noopener"
                target="_blank"
              >
                {{ provider.url }}
              </a>
            </div>

            <div class="flex items-center justify-between border-t border-border pt-3">
              <div class="flex items-center gap-4">
                <div data-testid="provider-projects-count">
                  <p class="text-lg font-bold tabular-nums text-text">
                    {{ provider.projectsCount }}
                  </p>
                  <p class="text-xs text-text-muted">
                    {{ t('catalog.providers.projects') }}
                  </p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-xs text-text-muted">
                  {{ t('catalog.providers.lastSync') }}
                </p>
                <p class="text-xs text-text-muted">
                  {{ provider.lastSyncAt ? d(new Date(provider.lastSyncAt), 'short') : '—' }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <div
          v-else
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
