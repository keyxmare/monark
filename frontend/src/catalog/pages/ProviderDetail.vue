<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import type { RemoteProject } from '@/catalog/types/provider'

import { useProviderStore } from '@/catalog/stores/provider'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'

const route = useRoute()
const router = useRouter()
const { t, d } = useI18n()
const providerStore = useProviderStore()

const providerId = computed(() => route.params.id as string)
const selectedIds = ref<string[]>([])
const testingConnection = ref(false)
const importing = ref(false)
const syncing = ref(false)
const syncMessage = ref<string | null>(null)

onMounted(async () => {
  await providerStore.fetchOne(providerId.value)
  await providerStore.fetchRemoteProjects(providerId.value)
})

function getSelectedRemoteProjects(): RemoteProject[] {
  return providerStore.remoteProjects.filter(rp => selectedIds.value.includes(rp.externalId))
}

async function handleDelete() {
  await providerStore.remove(providerId.value)
  router.push({ name: 'catalog-providers-list' })
}

async function handleImport() {
  const selected = getSelectedRemoteProjects()
  if (selected.length === 0) return

  importing.value = true
  try {
    await providerStore.importProjects(providerId.value, {
      projects: selected.map(rp => ({
        defaultBranch: rp.defaultBranch,
        description: rp.description,
        externalId: rp.externalId,
        name: rp.name,
        repositoryUrl: rp.repositoryUrl,
        slug: rp.slug,
        visibility: rp.visibility,
      })),
    })
    selectedIds.value = []
  } finally {
    importing.value = false
  }
}

async function handleTestConnection() {
  testingConnection.value = true
  await providerStore.testConnection(providerId.value)
  testingConnection.value = false
}

async function handleSyncAll() {
  syncing.value = true
  syncMessage.value = null
  try {
    const count = await providerStore.syncAll(providerId.value)
    syncMessage.value = t('catalog.providers.syncStarted', { count })
  } catch {
    syncMessage.value = t('common.errors.failedToSync')
  } finally {
    syncing.value = false
  }
}

async function handlePageChange(page: number) {
  selectedIds.value = []
  await providerStore.fetchRemoteProjects(providerId.value, page)
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="provider-detail-page">
      <div class="mb-6 flex items-center justify-between">
        <RouterLink
          :to="{ name: 'catalog-providers-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="provider-detail-back"
        >
          &larr; {{ t('common.backTo', { page: t('catalog.providers.title').toLowerCase() }) }}
        </RouterLink>
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
            @click="handleDelete"
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
        <div
          class="mb-6 max-w-2xl rounded-xl border border-border bg-surface p-6"
          data-testid="provider-detail-card"
        >
          <div class="mb-6 flex items-center gap-4">
            <h2 class="text-2xl font-bold text-text">
              {{ providerStore.selected.name }}
            </h2>
            <span
              :class="{
                'bg-green-100 text-green-800': providerStore.selected.status === 'connected',
                'bg-yellow-100 text-yellow-800': providerStore.selected.status === 'pending',
                'bg-red-100 text-red-800': providerStore.selected.status === 'error',
              }"
              class="rounded-full px-2 py-0.5 text-xs font-medium"
              data-testid="provider-detail-status"
            >
              {{ providerStore.selected.status }}
            </span>
          </div>

          <dl class="space-y-4">
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('catalog.providers.type') }}
              </dt>
              <dd class="mt-1">
                <span
                  :class="{
                    'bg-orange-100 text-orange-800': providerStore.selected.type === 'gitlab',
                    'bg-gray-100 text-gray-800': providerStore.selected.type === 'github',
                    'bg-blue-100 text-blue-800': providerStore.selected.type === 'bitbucket',
                  }"
                  class="rounded-full px-2 py-0.5 text-xs font-medium"
                  data-testid="provider-detail-type"
                >
                  {{ providerStore.selected.type }}
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('catalog.providers.url') }}
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="provider-detail-url"
              >
                {{ providerStore.selected.url }}
              </dd>
            </div>
            <div v-if="providerStore.selected.username">
              <dt class="text-sm font-medium text-text-muted">
                {{ t('catalog.providers.username') }}
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="provider-detail-username"
              >
                {{ providerStore.selected.username }}
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('catalog.providers.lastSync') }}
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="provider-detail-last-sync"
              >
                {{ providerStore.selected.lastSyncAt ? d(new Date(providerStore.selected.lastSyncAt), 'short') : t('common.never') }}
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('identity.users.createdAt') }}
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="provider-detail-created-at"
              >
                {{ d(new Date(providerStore.selected.createdAt), 'short') }}
              </dd>
            </div>
          </dl>

          <div class="mt-6">
            <button
              :disabled="testingConnection"
              class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
              data-testid="provider-test-connection"
              @click="handleTestConnection"
            >
              {{ testingConnection ? t('catalog.providers.testing') : t('catalog.providers.testConnection') }}
            </button>
          </div>
        </div>

        <div class="mt-8">
          <div class="mb-4 flex items-center justify-between">
            <h3 class="text-xl font-bold text-text">
              {{ t('catalog.providers.remoteProjects') }}
            </h3>
            <div class="flex items-center gap-3">
              <button
                :disabled="syncing"
                class="rounded-lg border border-primary bg-transparent px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary hover:text-white disabled:opacity-50"
                data-testid="provider-sync-all"
                @click="handleSyncAll"
              >
                {{ syncing ? t('catalog.providers.syncing') : t('catalog.providers.syncAll') }}
              </button>
              <button
                v-if="selectedIds.length > 0"
                :disabled="importing"
                class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
                data-testid="provider-import-selected"
                @click="handleImport"
              >
                {{ importing ? t('catalog.providers.importing') : t('catalog.providers.importSelected', { count: selectedIds.length }) }}
              </button>
            </div>
          </div>

          <div
            v-if="syncMessage"
            class="mb-4 rounded-lg px-4 py-3 text-sm"
            :class="syncMessage === t('common.errors.failedToSync') ? 'bg-danger/10 text-danger' : 'bg-green-100 text-green-800'"
            data-testid="provider-sync-message"
          >
            {{ syncMessage }}
          </div>

          <div
            v-if="providerStore.loading"
            class="py-8 text-center text-text-muted"
            data-testid="remote-projects-loading"
          >
            {{ t('catalog.providers.loadingRemote') }}
          </div>

          <div
            v-else
            class="rounded-xl border border-border bg-surface"
            data-testid="remote-projects-list"
          >
            <div
              v-for="project in providerStore.remoteProjects"
              :key="project.externalId"
              class="flex items-center gap-4 border-b border-border px-4 py-3 last:border-0"
            >
              <input
                v-if="!project.alreadyImported"
                v-model="selectedIds"
                type="checkbox"
                :value="project.externalId"
                :aria-label="t('catalog.providers.selectProject', { name: project.name })"
                :data-testid="`select-${project.externalId}`"
              >
              <span
                v-else
                class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800"
                data-testid="remote-project-imported-badge"
              >
                {{ t('catalog.providers.imported') }}
              </span>
              <div class="flex-1">
                <p class="font-medium text-text">
                  {{ project.name }}
                </p>
                <p class="text-sm text-text-muted">
                  {{ project.slug }}
                </p>
              </div>
              <span class="text-sm text-text-muted">
                {{ project.visibility }}
              </span>
              <span class="text-sm text-text-muted">
                {{ project.defaultBranch }}
              </span>
            </div>

            <div
              v-if="providerStore.remoteProjects.length === 0"
              class="py-8 text-center text-text-muted"
              data-testid="remote-projects-empty"
            >
              {{ t('catalog.providers.noRemoteProjects') }}
            </div>
          </div>

          <div
            v-if="providerStore.remoteProjectsTotalPages > 1"
            class="mt-4 flex items-center justify-between"
            data-testid="remote-projects-pagination"
          >
            <button
              :disabled="providerStore.remoteProjectsCurrentPage <= 1 || providerStore.loading"
              class="rounded-lg border border-border bg-surface px-4 py-2 text-sm font-medium text-text transition-colors hover:bg-surface-muted disabled:cursor-not-allowed disabled:opacity-50"
              data-testid="remote-projects-prev"
              @click="handlePageChange(providerStore.remoteProjectsCurrentPage - 1)"
            >
              {{ t('common.pagination.previous') }}
            </button>
            <span
              class="text-sm text-text-muted"
              data-testid="remote-projects-page-indicator"
            >
              {{ t('common.pagination.page', { current: providerStore.remoteProjectsCurrentPage, total: providerStore.remoteProjectsTotalPages }) }}
            </span>
            <button
              :disabled="providerStore.remoteProjectsCurrentPage >= providerStore.remoteProjectsTotalPages || providerStore.loading"
              class="rounded-lg border border-border bg-surface px-4 py-2 text-sm font-medium text-text transition-colors hover:bg-surface-muted disabled:cursor-not-allowed disabled:opacity-50"
              data-testid="remote-projects-next"
              @click="handlePageChange(providerStore.remoteProjectsCurrentPage + 1)"
            >
              {{ t('common.pagination.next') }}
            </button>
          </div>
        </div>
      </template>
    </div>
  </DashboardLayout>
</template>
