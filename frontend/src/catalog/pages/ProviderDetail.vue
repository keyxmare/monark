<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import type { RemoteProject } from '@/catalog/types/provider'

import ProviderInfoCard from '@/catalog/components/ProviderInfoCard.vue'
import ProviderStatsCards from '@/catalog/components/ProviderStatsCards.vue'
import RemoteProjectsSection from '@/catalog/components/RemoteProjectsSection.vue'
import { useSyncProgress } from '@/catalog/composables/useSyncProgress'
import { useProviderStore } from '@/catalog/stores/provider'
import ConfirmDialog from '@/shared/components/ConfirmDialog.vue'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useToastStore } from '@/shared/stores/toast'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const providerStore = useProviderStore()
const toastStore = useToastStore()
const { track } = useSyncProgress()

const apiLatency = ref<null | number>(null)
const importing = ref(false)
const initialLoaded = ref(false)
const showDeleteConfirm = ref(false)
const syncing = ref(false)
const testingConnection = ref(false)

const providerId = computed(() => route.params.id as string)
const syncFreshness = computed(() => {
  if (!providerStore.selected?.lastSyncAt) return 'stale'
  const diff = Date.now() - new Date(providerStore.selected.lastSyncAt).getTime()
  const hours = diff / (1000 * 60 * 60)
  if (hours < 1) return 'fresh'
  if (hours < 24) return 'recent'
  return 'stale'
})

onMounted(async () => {
  await providerStore.fetchOne(providerId.value)
  await providerStore.fetchRemoteProjects(providerId.value)
  initialLoaded.value = true
})

async function handleDelete() {
  showDeleteConfirm.value = false
  await providerStore.remove(providerId.value)
  router.push({ name: 'catalog-providers-list' })
}

async function handleImport(projects: RemoteProject[]) {
  importing.value = true
  try {
    await providerStore.importProjects(providerId.value, {
      projects: projects.map(rp => ({
        defaultBranch: rp.defaultBranch,
        description: rp.description,
        externalId: rp.externalId,
        name: rp.name,
        repositoryUrl: rp.repositoryUrl,
        slug: rp.slug,
        visibility: rp.visibility,
      })),
    })
    toastStore.addToast({
      title: t('catalog.providers.importSuccess'),
      variant: 'success',
    })
  } finally {
    importing.value = false
  }
}

async function handlePageChange(page: number) {
  await providerStore.fetchRemoteProjects(providerId.value, page)
}

async function handleSyncAll() {
  syncing.value = true
  try {
    const result = await providerStore.syncAll(providerId.value)
    track(result.id, result.projectsCount)
  } catch {
  } finally {
    syncing.value = false
  }
}

async function handleSyncSelected(localIds: string[]) {
  syncing.value = true
  try {
    const result = await providerStore.syncAll(providerId.value, false, localIds)
    track(result.id, result.projectsCount)
  } catch {
  } finally {
    syncing.value = false
  }
}

async function handleTestConnection() {
  testingConnection.value = true
  const start = performance.now()
  const connected = await providerStore.testConnection(providerId.value)
  apiLatency.value = Math.round(performance.now() - start)
  testingConnection.value = false
  toastStore.addToast({
    title: connected
      ? t('catalog.providers.connectionSuccess', { name: providerStore.selected?.name ?? '' })
      : t('catalog.providers.connectionFailed', { name: providerStore.selected?.name ?? '' }),
    variant: connected ? 'success' : 'error',
  })
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="provider-detail-page">
      <nav
        class="mb-6 flex items-center gap-1 text-sm text-text-muted"
        data-testid="provider-detail-breadcrumb"
      >
        <RouterLink
          :to="{ name: 'catalog-providers-list' }"
          class="text-primary hover:text-primary-dark"
        >
          {{ t('catalog.providers.title') }}
        </RouterLink>
        <span>/</span>
        <span
          v-if="providerStore.selected"
          class="font-medium text-text"
        >
          {{ providerStore.selected.name }}
        </span>
      </nav>

      <div class="mb-6 flex items-end justify-between">
        <div />
        <div
          v-if="providerStore.selected"
          class="flex items-center gap-3"
        >
          <RouterLink
            :to="{ name: 'catalog-providers-edit', params: { id: providerStore.selected.id } }"
            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
            data-testid="provider-detail-edit"
          >
            {{ t('common.actions.edit') }}
          </RouterLink>
          <button
            class="rounded-lg bg-danger px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-danger/80"
            data-testid="provider-detail-delete"
            @click="showDeleteConfirm = true"
          >
            {{ t('common.actions.delete') }}
          </button>
        </div>
      </div>

      <div
        v-if="providerStore.loading && !providerStore.selected"
        class="py-8 text-center text-text-muted"
        data-testid="provider-detail-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="providerStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="provider-detail-error"
      >
        {{ providerStore.error }}
      </div>

      <template v-else-if="providerStore.selected">
        <ProviderInfoCard
          :provider="providerStore.selected"
          :testing-connection="testingConnection"
          @test-connection="handleTestConnection"
        />

        <ProviderStatsCards
          :api-latency="apiLatency"
          :projects-count="providerStore.selected.projectsCount"
          :status="providerStore.selected.status"
          :sync-freshness="syncFreshness"
        />

        <RemoteProjectsSection
          :importing="importing"
          :initial-loaded="initialLoaded"
          :projects="providerStore.remoteProjects"
          :remote-projects-current-page="providerStore.remoteProjectsCurrentPage"
          :remote-projects-total-pages="providerStore.remoteProjectsTotalPages"
          :syncing="syncing"
          @import="handleImport"
          @page-change="handlePageChange"
          @sync-all="handleSyncAll"
          @sync-selected="handleSyncSelected"
        />
      </template>
      <ConfirmDialog
        :open="showDeleteConfirm"
        :title="t('catalog.providers.confirmDeleteTitle')"
        :message="t('catalog.providers.confirmDeleteMessage', { name: providerStore.selected?.name ?? '' })"
        :confirm-label="t('common.actions.delete')"
        variant="danger"
        @confirm="handleDelete"
        @cancel="showDeleteConfirm = false"
      />
    </div>
  </DashboardLayout>
</template>
