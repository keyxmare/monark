<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'

import type { RemoteProject } from '@/catalog/types/provider'

import Pagination from '@/shared/components/Pagination.vue'

type SortField = 'defaultBranch' | 'name' | 'visibility'

const props = defineProps<{
  importing: boolean
  initialLoaded: boolean
  projects: RemoteProject[]
  remoteProjectsCurrentPage: number
  remoteProjectsTotalPages: number
  syncing: boolean
}>()

const emit = defineEmits<{
  import: [projects: RemoteProject[]]
  pageChange: [page: number]
  syncAll: []
  syncSelected: [localIds: string[]]
}>()

const MIN_SEARCH_LENGTH = 3
const { t } = useI18n()

const filterVisibility = ref('all')
const searching = ref(false)
const searchQuery = ref('')
const selectedIds = ref<string[]>([])
const sortDir = ref<'asc' | 'desc'>('asc')
const sortField = ref<SortField>('name')
let searchDebounce: null | ReturnType<typeof setTimeout> = null

const displayedProjects = computed(() => {
  let projects = props.projects
  if (searchQuery.value && searchQuery.value.length < MIN_SEARCH_LENGTH) {
    const q = searchQuery.value.toLowerCase()
    projects = projects.filter(rp => rp.name.toLowerCase().includes(q) || rp.slug.toLowerCase().includes(q))
  }
  const field = sortField.value
  const dir = sortDir.value === 'asc' ? 1 : -1
  return [...projects].sort((a, b) => a[field].toLowerCase().localeCompare(b[field].toLowerCase()) * dir)
})

const allSelected = computed(() =>
  displayedProjects.value.length > 0 && selectedIds.value.length === displayedProjects.value.length,
)
const selectedImportable = computed(() =>
  selectedIds.value.filter(id => displayedProjects.value.some(rp => rp.externalId === id && !rp.alreadyImported)),
)
const selectedSyncable = computed(() =>
  selectedIds.value.filter(id => displayedProjects.value.some(rp => rp.externalId === id && rp.alreadyImported && rp.localProjectId)),
)
const someSelected = computed(() =>
  selectedIds.value.length > 0 && selectedIds.value.length < displayedProjects.value.length,
)

watch(searchQuery, () => {
  if (searchDebounce) clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => handleFilterChange(), 300)
})

watch([filterVisibility, sortField, sortDir], () => {
  handleFilterChange()
})

function handleFilterChange() {
  selectedIds.value = []
  searching.value = true
  emit('pageChange', 1)
  searching.value = false
}

function handleImport() {
  const selected = props.projects
    .filter(rp => selectedIds.value.includes(rp.externalId) && !rp.alreadyImported)
  if (selected.length === 0) return
  emit('import', selected)
  selectedIds.value = []
}

function handleSyncSelected() {
  const localIds = selectedSyncable.value
    .map(externalId => displayedProjects.value.find(rp => rp.externalId === externalId)?.localProjectId)
    .filter((id): id is string => id != null)
  if (localIds.length === 0) return
  emit('syncSelected', localIds)
  selectedIds.value = []
}

function toggleSelectAll() {
  if (allSelected.value) {
    selectedIds.value = []
  } else {
    selectedIds.value = displayedProjects.value.map(rp => rp.externalId)
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
          @click="$emit('syncAll')"
        >
          {{ syncing ? t('catalog.providers.syncing') : t('catalog.providers.syncAll') }}
        </button>
        <button
          v-if="selectedImportable.length > 0"
          :disabled="importing"
          class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
          data-testid="provider-import-selected"
          @click="handleImport"
        >
          {{ importing ? t('catalog.providers.importing') : t('catalog.providers.importSelected', { count: selectedImportable.length }) }}
        </button>
        <button
          v-if="selectedSyncable.length > 0"
          :disabled="syncing"
          class="rounded-lg border border-primary bg-transparent px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary hover:text-white disabled:opacity-50"
          data-testid="provider-sync-selected"
          @click="handleSyncSelected"
        >
          {{ t('catalog.providers.syncSelected', { count: selectedSyncable.length }) }}
        </button>
      </div>
    </div>

    <div
      v-if="!initialLoaded"
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
            class="w-full rounded-lg border border-border bg-surface py-2 pl-9 pr-9 text-sm text-text placeholder:text-text-muted focus:border-primary focus:outline-none"
            data-testid="remote-projects-search"
          >
          <svg
            v-if="searching"
            class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-primary"
            data-testid="search-spinner"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            />
            <path
              class="opacity-75"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
              fill="currentColor"
            />
          </svg>
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
        v-if="displayedProjects.length > 0"
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
            <RouterLink
              v-if="project.alreadyImported && project.localProjectId"
              :to="{ name: 'catalog-projects-detail', params: { id: project.localProjectId } }"
              class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 transition-colors hover:bg-green-200"
              data-testid="remote-project-imported-badge"
            >
              {{ t('catalog.providers.imported') }} &rarr;
            </RouterLink>
            <span
              v-else-if="project.alreadyImported"
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

    <Pagination
      v-if="remoteProjectsTotalPages > 1"
      :page="remoteProjectsCurrentPage"
      :total-pages="remoteProjectsTotalPages"
      data-testid="remote-projects-pagination"
      @update:page="$emit('pageChange', $event)"
    />
  </div>
</template>
