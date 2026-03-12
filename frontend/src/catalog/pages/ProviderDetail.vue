<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import type { RemoteProject } from '@/catalog/types/provider'

import ProviderIcon from '@/catalog/components/ProviderIcon.vue'
import { useSyncProgress } from '@/catalog/composables/useSyncProgress'
import { useProviderStore } from '@/catalog/stores/provider'
import ConfirmDialog from '@/shared/components/ConfirmDialog.vue'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useToastStore } from '@/shared/stores/toast'

type SortField = 'defaultBranch' | 'name' | 'visibility'

const route = useRoute()
const router = useRouter()
const { d, t } = useI18n()
const providerStore = useProviderStore()
const toastStore = useToastStore()
const { track } = useSyncProgress()

const MIN_SEARCH_LENGTH = 3
const providerId = computed(() => route.params.id as string)
const displayedProjects = computed(() => {
  let projects = providerStore.remoteProjects
  if (searchQuery.value && searchQuery.value.length < MIN_SEARCH_LENGTH) {
    const q = searchQuery.value.toLowerCase()
    projects = projects.filter(rp => rp.name.toLowerCase().includes(q) || rp.slug.toLowerCase().includes(q))
  }
  const field = sortField.value
  const dir = sortDir.value === 'asc' ? 1 : -1
  return [...projects].sort((a, b) => a[field].toLowerCase().localeCompare(b[field].toLowerCase()) * dir)
})
const selectableProjects = computed(() =>
  displayedProjects.value.filter(rp => !rp.alreadyImported),
)
const allSelected = computed(() =>
  selectableProjects.value.length > 0 && selectedIds.value.length === selectableProjects.value.length,
)
const someSelected = computed(() =>
  selectedIds.value.length > 0 && selectedIds.value.length < selectableProjects.value.length,
)

const filterVisibility = ref('all')
const importing = ref(false)
const searchQuery = ref('')
const selectedIds = ref<string[]>([])
const showDeleteConfirm = ref(false)
const sortDir = ref<'asc' | 'desc'>('asc')
const sortField = ref<SortField>('name')
const syncing = ref(false)
const testingConnection = ref(false)
let searchDebounce: null | ReturnType<typeof setTimeout> = null

function fetchFilteredProjects(page = 1) {
  selectedIds.value = []
  providerStore.fetchRemoteProjects(providerId.value, page, 20, {
    search: searchQuery.value.length >= MIN_SEARCH_LENGTH ? searchQuery.value : undefined,
    sort: sortField.value,
    sortDir: sortDir.value,
    visibility: filterVisibility.value !== 'all' ? filterVisibility.value : undefined,
  })
}

watch(searchQuery, () => {
  if (searchDebounce) clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => fetchFilteredProjects(), 300)
})

watch([filterVisibility, sortField, sortDir], () => {
  fetchFilteredProjects()
})

onMounted(async () => {
  await providerStore.fetchOne(providerId.value)
  await providerStore.fetchRemoteProjects(providerId.value)
})

function getSelectedRemoteProjects(): RemoteProject[] {
  return providerStore.remoteProjects.filter(rp => selectedIds.value.includes(rp.externalId))
}

async function handleDelete() {
  showDeleteConfirm.value = false
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

async function handlePageChange(page: number) {
  fetchFilteredProjects(page)
}

async function handleSyncAll() {
  syncing.value = true
  try {
    const result = await providerStore.syncAll(providerId.value)
    track(result.id, result.projectsCount)
  } catch {
    // error handled by store
  } finally {
    syncing.value = false
  }
}

async function handleTestConnection() {
  testingConnection.value = true
  const connected = await providerStore.testConnection(providerId.value)
  testingConnection.value = false
  toastStore.addToast({
    title: connected
      ? t('catalog.providers.connectionSuccess', { name: providerStore.selected?.name ?? '' })
      : t('catalog.providers.connectionFailed', { name: providerStore.selected?.name ?? '' }),
    variant: connected ? 'success' : 'error',
  })
}

function toggleSelectAll() {
  if (allSelected.value) {
    selectedIds.value = []
  } else {
    selectedIds.value = selectableProjects.value.map(rp => rp.externalId)
  }
}

function toggleSort(field: SortField) {
  if (sortField.value === field) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDir.value = 'asc'
  }
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
        <div
          class="mb-6 max-w-3xl rounded-xl border border-border bg-surface"
          data-testid="provider-detail-card"
        >
          <div class="flex items-center justify-between border-b border-border px-6 py-4">
            <div class="flex items-center gap-3">
              <ProviderIcon
                :type="providerStore.selected.type"
                :size="28"
              />
              <div>
                <h2 class="text-xl font-bold text-text">
                  {{ providerStore.selected.name }}
                </h2>
                <p class="text-xs text-text-muted">
                  {{ t(`catalog.providers.types.${providerStore.selected.type}`) }}
                </p>
              </div>
              <span
                :class="{
                  'bg-green-100 text-green-800': providerStore.selected.status === 'connected',
                  'bg-yellow-100 text-yellow-800': providerStore.selected.status === 'pending',
                  'bg-red-100 text-red-800': providerStore.selected.status === 'error',
                }"
                class="rounded-full px-2 py-0.5 text-xs font-medium"
                data-testid="provider-detail-status"
              >
                {{ t(`catalog.providers.statuses.${providerStore.selected.status}`) }}
              </span>
            </div>
            <button
              :disabled="testingConnection"
              class="rounded-lg border border-primary bg-transparent px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary hover:text-white disabled:opacity-50"
              data-testid="provider-test-connection"
              @click="handleTestConnection"
            >
              {{ testingConnection ? t('catalog.providers.testing') : t('catalog.providers.testConnection') }}
            </button>
          </div>

          <dl
            class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2"
            data-testid="provider-detail-fields"
          >
            <div class="flex items-start gap-3">
              <svg
                class="mt-0.5 h-4 w-4 shrink-0 text-text-muted"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5a17.92 17.92 0 01-8.716-2.247"
                />
              </svg>
              <div>
                <dt class="text-xs font-medium text-text-muted">
                  {{ t('catalog.providers.url') }}
                </dt>
                <dd class="mt-0.5">
                  <a
                    :href="providerStore.selected.url"
                    class="text-sm text-primary hover:underline"
                    data-testid="provider-detail-url"
                    rel="noopener"
                    target="_blank"
                  >
                    {{ providerStore.selected.url }}
                  </a>
                </dd>
              </div>
            </div>

            <div
              v-if="providerStore.selected.username"
              class="flex items-start gap-3"
            >
              <svg
                class="mt-0.5 h-4 w-4 shrink-0 text-text-muted"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
                />
              </svg>
              <div>
                <dt class="text-xs font-medium text-text-muted">
                  {{ t('catalog.providers.username') }}
                </dt>
                <dd
                  class="mt-0.5 text-sm text-text"
                  data-testid="provider-detail-username"
                >
                  {{ providerStore.selected.username }}
                </dd>
              </div>
            </div>

            <div class="flex items-start gap-3">
              <svg
                class="mt-0.5 h-4 w-4 shrink-0 text-text-muted"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"
                />
              </svg>
              <div>
                <dt class="text-xs font-medium text-text-muted">
                  {{ t('catalog.providers.lastSync') }}
                </dt>
                <dd
                  class="mt-0.5 text-sm text-text"
                  data-testid="provider-detail-last-sync"
                >
                  {{ providerStore.selected.lastSyncAt ? d(new Date(providerStore.selected.lastSyncAt), 'short') : t('common.never') }}
                </dd>
              </div>
            </div>

            <div class="flex items-start gap-3">
              <svg
                class="mt-0.5 h-4 w-4 shrink-0 text-text-muted"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"
                />
              </svg>
              <div>
                <dt class="text-xs font-medium text-text-muted">
                  {{ t('identity.users.createdAt') }}
                </dt>
                <dd
                  class="mt-0.5 text-sm text-text"
                  data-testid="provider-detail-created-at"
                >
                  {{ d(new Date(providerStore.selected.createdAt), 'short') }}
                </dd>
              </div>
            </div>
          </dl>
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
            v-if="providerStore.loading"
            class="py-8 text-center text-text-muted"
            data-testid="remote-projects-loading"
          >
            {{ t('catalog.providers.loadingRemote') }}
          </div>

          <template v-else>
            <div
              class="mb-4 flex flex-wrap items-center gap-3"
              data-testid="remote-projects-filters"
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
                  v-model="searchQuery"
                  type="search"
                  :aria-label="t('catalog.providers.searchProjects')"
                  :placeholder="t('catalog.providers.searchProjects')"
                  class="w-full rounded-lg border border-border bg-surface py-2 pl-9 pr-3 text-sm text-text placeholder:text-text-muted focus:border-primary focus:outline-none"
                  data-testid="remote-projects-search"
                >
              </div>
              <select
                v-model="filterVisibility"
                :aria-label="t('catalog.providers.allVisibilities')"
                class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"
                data-testid="remote-projects-filter-visibility"
              >
                <option value="all">
                  {{ t('catalog.providers.allVisibilities') }}
                </option>
                <option value="public">
                  {{ t('catalog.providers.visibility.public') }}
                </option>
                <option value="private">
                  {{ t('catalog.providers.visibility.private') }}
                </option>
                <option value="internal">
                  {{ t('catalog.providers.visibility.internal') }}
                </option>
              </select>
              <div
                class="flex items-center gap-1"
                data-testid="remote-projects-sort"
              >
                <button
                  v-for="field in (['name', 'visibility', 'defaultBranch'] as const)"
                  :key="field"
                  :class="sortField === field ? 'border-primary bg-primary/10 text-primary' : 'border-border text-text-muted hover:border-primary/50'"
                  class="rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-colors"
                  :data-testid="`sort-${field}`"
                  @click="toggleSort(field)"
                >
                  {{ t(`catalog.providers.sort.${field}`) }}
                  <span v-if="sortField === field">
                    {{ sortDir === 'asc' ? '↑' : '↓' }}
                  </span>
                </button>
              </div>
            </div>

            <div
              v-if="selectableProjects.length > 0"
              class="mb-4 flex items-center gap-3"
              data-testid="select-all-header"
            >
              <input
                type="checkbox"
                :checked="allSelected"
                :indeterminate="someSelected"
                :aria-label="t('catalog.providers.selectAll')"
                data-testid="select-all-checkbox"
                @change="toggleSelectAll"
              >
              <span class="text-sm text-text-muted">
                {{ t('catalog.providers.selectAll') }}
              </span>
            </div>

            <div
              v-if="displayedProjects.length > 0"
              class="grid grid-cols-1 gap-4 sm:grid-cols-2"
              data-testid="remote-projects-list"
            >
              <div
                v-for="project in displayedProjects"
                :key="project.externalId"
                class="rounded-xl border border-border bg-surface p-4 transition-shadow hover:shadow-md"
                data-testid="remote-project-card"
              >
                <div class="mb-2 flex items-start justify-between">
                  <div class="flex items-center gap-3">
                    <input
                      v-if="!project.alreadyImported"
                      v-model="selectedIds"
                      type="checkbox"
                      :value="project.externalId"
                      :aria-label="t('catalog.providers.selectProject', { name: project.name })"
                      :data-testid="`select-${project.externalId}`"
                    >
                    <div>
                      <p class="font-medium text-text">
                        {{ project.name }}
                      </p>
                      <p class="text-xs text-text-muted">
                        {{ project.slug }}
                      </p>
                    </div>
                  </div>
                  <span
                    v-if="project.alreadyImported"
                    class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800"
                    data-testid="remote-project-imported-badge"
                  >
                    {{ t('catalog.providers.imported') }}
                  </span>
                </div>
                <div class="flex items-center gap-3 border-t border-border pt-2">
                  <span
                    :class="{
                      'bg-blue-100 text-blue-800': project.visibility === 'public',
                      'bg-gray-100 text-gray-800': project.visibility === 'private',
                      'bg-yellow-100 text-yellow-800': project.visibility === 'internal',
                    }"
                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                  >
                    {{ t(`catalog.providers.visibility.${project.visibility}`) }}
                  </span>
                  <span class="flex items-center gap-1 text-xs text-text-muted">
                    <svg
                      class="h-3.5 w-3.5"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="1.5"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5"
                      />
                    </svg>
                    {{ project.defaultBranch }}
                  </span>
                </div>
              </div>
            </div>

            <div
              v-else
              class="flex flex-col items-center rounded-xl border border-border bg-surface py-12"
              data-testid="remote-projects-empty"
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
                  d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"
                />
              </svg>
              <p class="mb-1 text-sm font-medium text-text">
                {{ t('catalog.providers.noRemoteProjects') }}
              </p>
              <p class="text-sm text-text-muted">
                {{ t('catalog.providers.noRemoteProjectsHint') }}
              </p>
            </div>
          </template>

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
