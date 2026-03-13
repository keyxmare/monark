<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute } from 'vue-router'

import { useMergeRequestStore } from '@/catalog/stores/merge-request'
import { useProjectStore } from '@/catalog/stores/project'
import { useTechStackStore } from '@/catalog/stores/tech-stack'
import { useDependencyStore } from '@/dependency/stores/dependency'
import ConfirmDialog from '@/shared/components/ConfirmDialog.vue'
import Pagination from '@/shared/components/Pagination.vue'
import TechBadge from '@/shared/components/TechBadge.vue'
import { useConfirmDelete } from '@/shared/composables/useConfirmDelete'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useToastStore } from '@/shared/stores/toast'

const PER_PAGE = 20

const route = useRoute()
const { d, t } = useI18n()
const projectStore = useProjectStore()
const techStackStore = useTechStackStore()
const dependencyStore = useDependencyStore()
const mergeRequestStore = useMergeRequestStore()
const toastStore = useToastStore()
const { target: deleteTarget, isOpen: deleteOpen, requestDelete, cancel: cancelDelete, confirm: confirmDelete } = useConfirmDelete<{ id: string; name: string }>()

const activeTab = ref<'dependencies' | 'merge-requests' | 'tech-stacks'>('tech-stacks')
const projectId = computed(() => route.params.id as string)
const depSearch = ref('')
const depFilterPm = ref('all')
const depTypeFilter = ref('')
const mrStatusFilter = ref('')
const mrAuthorSearch = ref('')

const scanFreshness = computed(() => {
  if (!projectStore.selected?.updatedAt) return 'stale'
  const diff = Date.now() - new Date(projectStore.selected.updatedAt).getTime()
  const hours = diff / (1000 * 60 * 60)
  if (hours < 1) return 'fresh'
  if (hours < 24) return 'recent'
  return 'stale'
})

const uniqueTechNames = computed(() => {
  const names = new Set<string>()
  for (const ts of techStackStore.techStacks) {
    names.add(ts.language)
    if (ts.framework) names.add(ts.framework)
  }
  return [...names]
})

const filteredDependencies = computed(() => {
  let deps = dependencyStore.dependencies
  if (depSearch.value.trim()) {
    const q = depSearch.value.toLowerCase()
    deps = deps.filter(dep => dep.name.toLowerCase().includes(q))
  }
  if (depFilterPm.value !== 'all') {
    deps = deps.filter(dep => dep.packageManager === depFilterPm.value)
  }
  if (depTypeFilter.value) {
    deps = deps.filter(dep => dep.type === depTypeFilter.value)
  }
  return deps
})

const filteredMergeRequests = computed(() => {
  return mergeRequestStore.mergeRequests.filter(mr => {
    if (mrStatusFilter.value && mr.status !== mrStatusFilter.value) return false
    if (mrAuthorSearch.value && !mr.author.toLowerCase().includes(mrAuthorSearch.value.toLowerCase())) return false
    return true
  })
})

function truncateUrl(url: string, max = 50): string {
  if (url.length <= max) return url
  return `${url.slice(0, max)}…`
}

onMounted(async () => {
  await projectStore.fetchOne(projectId.value)
  await Promise.all([
    techStackStore.fetchAll(1, PER_PAGE, projectId.value),
    dependencyStore.fetchAll(1, PER_PAGE, projectId.value),
    mergeRequestStore.fetchAll(projectId.value, 1, PER_PAGE, 'active'),
  ])
})

watch(activeTab, (tab) => {
  if (tab === 'tech-stacks' && techStackStore.currentPage !== 1) {
    techStackStore.fetchAll(1, PER_PAGE, projectId.value)
  } else if (tab === 'dependencies' && dependencyStore.currentPage !== 1) {
    dependencyStore.fetchAll(1, PER_PAGE, projectId.value)
  } else if (tab === 'merge-requests' && mergeRequestStore.currentPage !== 1) {
    mergeRequestStore.fetchAll(projectId.value, 1, PER_PAGE, 'active')
  }
})

async function handleScan() {
  await projectStore.scan(projectId.value)
  toastStore.addToast({
    title: t('catalog.projects.scanComplete', {
      deps: projectStore.scanResult?.dependenciesDetected ?? 0,
      stacks: projectStore.scanResult?.stacksDetected ?? 0,
    }),
    variant: 'success',
  })
  await Promise.all([
    techStackStore.fetchAll(1, PER_PAGE, projectId.value),
    dependencyStore.fetchAll(1, PER_PAGE, projectId.value),
  ])
}

function changeTechStackPage(page: number) {
  techStackStore.fetchAll(page, PER_PAGE, projectId.value)
}

function changeDependencyPage(page: number) {
  dependencyStore.fetchAll(page, PER_PAGE, projectId.value)
}

function changeMergeRequestPage(page: number) {
  mergeRequestStore.fetchAll(projectId.value, page, PER_PAGE, 'active')
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="project-detail-page">
      <nav
        class="mb-6 flex items-center gap-1 text-sm text-text-muted"
        data-testid="project-detail-breadcrumb"
      >
        <RouterLink
          :to="{ name: 'catalog-projects-list' }"
          class="text-primary hover:text-primary-dark"
        >
          {{ t('catalog.projects.title') }}
        </RouterLink>
        <span>/</span>
        <span
          v-if="projectStore.selected"
          class="font-medium text-text"
        >
          {{ projectStore.selected.name }}
        </span>
      </nav>

      <div
        v-if="projectStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="project-detail-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="projectStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="project-detail-error"
      >
        {{ projectStore.error }}
      </div>

      <template v-else-if="projectStore.selected">
        <div class="mb-6 flex items-start justify-between">
          <div>
            <h2 class="text-2xl font-bold text-text">
              {{ projectStore.selected.name }}
            </h2>
            <p
              v-if="projectStore.selected.description"
              class="mt-1 text-sm text-text-muted"
              data-testid="project-detail-description"
            >
              {{ projectStore.selected.description }}
            </p>
            <p class="mt-1 text-sm text-text-muted">
              <a
                :href="projectStore.selected.repositoryUrl"
                target="_blank"
                rel="noopener"
                class="text-primary hover:text-primary-dark"
                data-testid="project-detail-repository-url"
                :title="projectStore.selected.repositoryUrl"
              >{{ truncateUrl(projectStore.selected.repositoryUrl) }} ↗</a>
            </p>
          </div>
          <div class="flex gap-2">
            <button
              v-if="projectStore.selected.externalId"
              :disabled="projectStore.scanning"
              class="rounded-lg border border-primary bg-transparent px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary hover:text-white disabled:opacity-50"
              data-testid="project-scan-btn"
              @click="handleScan"
            >
              {{ projectStore.scanning ? t('catalog.projects.scanning') : t('catalog.projects.scanProject') }}
            </button>
            <RouterLink
              :to="{ name: 'catalog-projects-edit', params: { id: projectStore.selected.id } }"
              class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
              data-testid="project-detail-edit"
            >
              {{ t('common.actions.edit') }}
            </RouterLink>
          </div>
        </div>

        <div
          class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4"
          data-testid="project-stats-cards"
        >
          <div class="rounded-xl border border-border bg-surface p-4 text-center">
            <div class="text-lg font-bold text-text">
              <span
                :class="[
                  'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium',
                  projectStore.selected.visibility === 'public'
                    ? 'bg-success/10 text-success'
                    : 'bg-warning/10 text-warning',
                ]"
                data-testid="project-stat-visibility"
              >
                {{ projectStore.selected.visibility }}
              </span>
            </div>
            <p class="mt-1 text-xs text-text-muted">
              {{ t('catalog.projects.visibility') }}
            </p>
          </div>

          <div class="rounded-xl border border-border bg-surface p-4 text-left">
            <p class="mb-2 text-xs text-text-muted">
              {{ t('catalog.projects.techStacks') }}
            </p>
            <div
              v-if="techStackStore.techStacks.length > 0"
              class="flex flex-wrap gap-2"
              data-testid="project-stat-stacks"
            >
              <TechBadge
                v-for="name in uniqueTechNames"
                :key="name"
                :name="name"
                size="md"
              />
            </div>
            <p
              v-else
              class="text-sm text-text-muted"
              data-testid="project-stat-stacks"
            >
              —
            </p>
          </div>

          <div class="rounded-xl border border-border bg-surface p-4 text-center">
            <div
              class="text-lg font-bold tabular-nums text-text"
              data-testid="project-stat-mrs"
            >
              {{ mergeRequestStore.total }}
            </div>
            <p class="mt-1 text-xs text-text-muted">
              {{ t('catalog.projects.mergeRequests') }}
            </p>
          </div>

          <div class="rounded-xl border border-border bg-surface p-4 text-center">
            <div
              :class="{
                'text-green-600': scanFreshness === 'fresh',
                'text-yellow-600': scanFreshness === 'recent',
                'text-red-600': scanFreshness === 'stale',
              }"
              class="text-lg font-bold"
              data-testid="project-stat-freshness"
            >
              {{ t(`catalog.projects.freshness.${scanFreshness}`) }}
            </div>
            <p class="mt-1 text-xs text-text-muted">
              {{ t('catalog.projects.lastScan') }}
            </p>
          </div>
        </div>

        <div class="mb-4 flex gap-2 border-b border-border">
          <button
            :class="[
              'px-4 py-2 text-sm font-medium transition-colors',
              activeTab === 'tech-stacks'
                ? 'border-b-2 border-primary text-primary'
                : 'text-text-muted hover:text-text',
            ]"
            data-testid="tab-tech-stacks"
            @click="activeTab = 'tech-stacks'"
          >
            {{ t('catalog.projects.techStacksCount', { count: techStackStore.total }) }}
          </button>
          <button
            :class="[
              'px-4 py-2 text-sm font-medium transition-colors',
              activeTab === 'dependencies'
                ? 'border-b-2 border-primary text-primary'
                : 'text-text-muted hover:text-text',
            ]"
            data-testid="tab-dependencies"
            @click="activeTab = 'dependencies'"
          >
            {{ t('catalog.projects.dependenciesCount', { count: dependencyStore.total }) }}
          </button>
          <button
            :class="[
              'px-4 py-2 text-sm font-medium transition-colors',
              activeTab === 'merge-requests'
                ? 'border-b-2 border-primary text-primary'
                : 'text-text-muted hover:text-text',
            ]"
            data-testid="tab-merge-requests"
            @click="activeTab = 'merge-requests'"
          >
            {{ t('catalog.projects.mergeRequestsCount', { count: mergeRequestStore.total }) }}
          </button>
        </div>

        <!-- Tech Stacks Tab -->
        <div
          v-if="activeTab === 'tech-stacks'"
          data-testid="tech-stacks-panel"
        >
          <div class="overflow-hidden rounded-xl border border-border bg-surface">
            <table class="w-full">
              <thead>
                <tr class="border-b border-border bg-surface-muted">
                  <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                    {{ t('catalog.techStacks.language') }}
                  </th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                    {{ t('catalog.techStacks.languageVersion') }}
                  </th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                    {{ t('catalog.techStacks.framework') }}
                  </th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                    {{ t('catalog.techStacks.frameworkVersion') }}
                  </th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                    {{ t('catalog.techStacks.detectedAt') }}
                  </th>
                  <th class="px-4 py-3 text-right text-sm font-medium text-text-muted">
                    {{ t('common.table.actions') }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="ts in techStackStore.techStacks"
                  :key="ts.id"
                  class="border-b border-border last:border-0"
                  data-testid="tech-stack-row"
                >
                  <td class="px-4 py-3 text-sm text-text">
                    {{ ts.language }}
                  </td>
                  <td class="px-4 py-3 text-sm text-text-muted">
                    {{ ts.version || '—' }}
                  </td>
                  <td class="px-4 py-3 text-sm text-text">
                    {{ ts.framework }}
                  </td>
                  <td class="px-4 py-3 text-sm text-text-muted">
                    {{ ts.frameworkVersion || '—' }}
                  </td>
                  <td class="px-4 py-3 text-sm text-text-muted">
                    {{ d(new Date(ts.detectedAt), 'short') }}
                  </td>
                  <td class="px-4 py-3 text-right">
                    <button
                      class="text-sm text-danger hover:text-danger/80"
                      data-testid="tech-stack-delete"
                      @click="requestDelete({ id: ts.id, name: `${ts.language} ${ts.framework}` })"
                    >
                      {{ t('common.actions.delete') }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
            <div
              v-if="techStackStore.techStacks.length === 0"
              class="py-8 text-center text-text-muted"
              data-testid="tech-stacks-empty"
            >
              {{ t('catalog.projects.noTechStacks') }}
            </div>
          </div>
          <Pagination
            v-if="techStackStore.totalPages > 1"
            :page="techStackStore.currentPage"
            :total-pages="techStackStore.totalPages"
            data-testid="tech-stacks-pagination"
            @update:page="changeTechStackPage"
          />
        </div>

        <!-- Dependencies Tab -->
        <div
          v-if="activeTab === 'dependencies'"
          data-testid="dependencies-panel"
        >
          <div
            class="mb-4 flex flex-wrap items-center gap-3"
            data-testid="dependencies-filters"
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
                v-model="depSearch"
                type="search"
                :aria-label="t('catalog.projects.searchDependencies')"
                :placeholder="t('catalog.projects.searchDependencies')"
                class="w-full rounded-lg border border-border bg-surface py-2 pl-9 pr-3 text-sm text-text placeholder:text-text-muted focus:border-primary focus:outline-none"
                data-testid="dependencies-search"
              >
            </div>
            <select
              v-model="depFilterPm"
              :aria-label="t('catalog.projects.allPackageManagers')"
              class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"
              data-testid="dependencies-filter-pm"
            >
              <option value="all">
                {{ t('catalog.projects.allPackageManagers') }}
              </option>
              <option value="composer">
                Composer
              </option>
              <option value="npm">
                npm
              </option>
              <option value="pip">
                pip
              </option>
            </select>
            <select
              v-model="depTypeFilter"
              :aria-label="t('catalog.projects.filterByType')"
              class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"
              data-testid="dependencies-filter-type"
            >
              <option value="">
                {{ t('catalog.projects.allTypes') }}
              </option>
              <option value="runtime">
                {{ t('dependency.dependencies.typeRuntime') }}
              </option>
              <option value="dev">
                {{ t('dependency.dependencies.typeDev') }}
              </option>
            </select>
          </div>

          <div class="overflow-hidden rounded-xl border border-border bg-surface">
            <table class="w-full">
              <thead>
                <tr class="border-b border-border bg-surface-muted">
                  <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                    {{ t('catalog.projects.name') }}
                  </th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                    {{ t('catalog.techStacks.version') }}
                  </th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                    {{ t('dependency.dependencies.packageManager') }}
                  </th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                    {{ t('dependency.dependencies.type') }}
                  </th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                    {{ t('catalog.projects.repository') }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="dep in filteredDependencies"
                  :key="dep.id"
                  class="border-b border-border last:border-0"
                  data-testid="dependency-row"
                >
                  <td class="px-4 py-3 text-sm text-text">
                    {{ dep.name }}
                  </td>
                  <td class="px-4 py-3 text-sm text-text-muted">
                    {{ dep.currentVersion }}
                  </td>
                  <td class="px-4 py-3">
                    <span class="rounded-full bg-info/10 px-2 py-0.5 text-xs font-medium text-info">
                      {{ dep.packageManager }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-text-muted">
                    {{ dep.type }}
                  </td>
                  <td class="px-4 py-3 text-sm">
                    <a
                      v-if="dep.repositoryUrl"
                      :href="dep.repositoryUrl"
                      target="_blank"
                      rel="noopener"
                      class="text-primary hover:text-primary-dark"
                      :title="dep.repositoryUrl"
                    >{{ truncateUrl(dep.repositoryUrl, 35) }} ↗</a>
                    <span
                      v-else
                      class="text-text-muted"
                    >—</span>
                  </td>
                </tr>
              </tbody>
            </table>
            <div
              v-if="filteredDependencies.length === 0"
              class="py-8 text-center text-text-muted"
              data-testid="dependencies-empty"
            >
              {{ depSearch || depFilterPm !== 'all' || depTypeFilter ? t('catalog.projects.noMatchingDependencies') : t('catalog.projects.noDependencies') }}
            </div>
          </div>
          <Pagination
            v-if="dependencyStore.totalPages > 1"
            :page="dependencyStore.currentPage"
            :total-pages="dependencyStore.totalPages"
            data-testid="dependencies-pagination"
            @update:page="changeDependencyPage"
          />
        </div>

        <!-- Merge Requests Tab -->
        <div
          v-if="activeTab === 'merge-requests'"
          data-testid="merge-requests-panel"
        >
          <div
            class="mb-4 flex flex-wrap items-center gap-3"
            data-testid="merge-requests-filters"
          >
            <select
              v-model="mrStatusFilter"
              :aria-label="t('catalog.projects.filterByStatus')"
              class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"
              data-testid="mr-filter-status"
            >
              <option value="">
                {{ t('catalog.mergeRequests.allStatuses') }}
              </option>
              <option value="open">
                {{ t('catalog.mergeRequests.statusOpen') }}
              </option>
              <option value="draft">
                {{ t('catalog.mergeRequests.statusDraft') }}
              </option>
              <option value="merged">
                {{ t('catalog.mergeRequests.statusMerged') }}
              </option>
              <option value="closed">
                {{ t('catalog.mergeRequests.statusClosed') }}
              </option>
            </select>
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
                v-model="mrAuthorSearch"
                type="search"
                :aria-label="t('catalog.mergeRequests.filterByAuthor')"
                :placeholder="t('catalog.mergeRequests.filterByAuthor')"
                class="w-full rounded-lg border border-border bg-surface py-2 pl-9 pr-3 text-sm text-text placeholder:text-text-muted focus:border-primary focus:outline-none"
                data-testid="mr-search-author"
              >
            </div>
          </div>
          <div class="overflow-hidden rounded-xl border border-border bg-surface">
            <table class="w-full">
              <thead>
                <tr class="border-b border-border bg-surface-muted">
                  <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                    {{ t('catalog.mergeRequests.mrTitle') }}
                  </th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                    {{ t('catalog.mergeRequests.status') }}
                  </th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                    {{ t('catalog.mergeRequests.author') }}
                  </th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                    {{ t('catalog.mergeRequests.branches') }}
                  </th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                    {{ t('catalog.mergeRequests.updatedAt') }}
                  </th>
                  <th class="px-4 py-3 text-right text-sm font-medium text-text-muted">
                    {{ t('common.table.actions') }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="mr in filteredMergeRequests"
                  :key="mr.id"
                  class="border-b border-border last:border-0"
                  data-testid="mr-row"
                >
                  <td class="px-4 py-3 text-sm text-text">
                    <span class="text-text-muted">#{{ mr.externalId }}</span>
                    {{ mr.title }}
                  </td>
                  <td class="px-4 py-3">
                    <span
                      :class="[
                        'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                        {
                          'bg-success/10 text-success': mr.status === 'open',
                          'bg-info/10 text-info': mr.status === 'merged',
                          'bg-danger/10 text-danger': mr.status === 'closed',
                          'bg-warning/10 text-warning': mr.status === 'draft',
                        },
                      ]"
                      data-testid="mr-status-badge"
                    >
                      {{ mr.status }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-text-muted">
                    {{ mr.author }}
                  </td>
                  <td class="px-4 py-3 text-sm text-text-muted">
                    {{ mr.sourceBranch }} → {{ mr.targetBranch }}
                  </td>
                  <td class="px-4 py-3 text-sm text-text-muted">
                    {{ d(new Date(mr.updatedAt), 'short') }}
                  </td>
                  <td class="px-4 py-3 text-right">
                    <a
                      :href="mr.url"
                      target="_blank"
                      rel="noopener"
                      class="text-sm text-primary hover:text-primary-dark"
                      data-testid="mr-external-link"
                    >
                      {{ t('catalog.mergeRequests.viewExternal') }} ↗
                    </a>
                  </td>
                </tr>
              </tbody>
            </table>
            <div
              v-if="filteredMergeRequests.length === 0"
              class="py-8 text-center text-text-muted"
              data-testid="merge-requests-empty"
            >
              {{ mrStatusFilter || mrAuthorSearch ? t('catalog.projects.noMatchingMergeRequests') : t('catalog.mergeRequests.noMergeRequests') }}
            </div>
          </div>
          <Pagination
            v-if="mergeRequestStore.totalPages > 1"
            :page="mergeRequestStore.currentPage"
            :total-pages="mergeRequestStore.totalPages"
            data-testid="merge-requests-pagination"
            @update:page="changeMergeRequestPage"
          />
        </div>
      </template>

      <ConfirmDialog
        :open="deleteOpen"
        :title="t('catalog.projects.confirmDeleteStackTitle')"
        :message="t('catalog.projects.confirmDeleteStackMessage', { name: deleteTarget?.name ?? '' })"
        :confirm-label="t('common.actions.delete')"
        variant="danger"
        @confirm="confirmDelete(() => techStackStore.remove(deleteTarget!.id))"
        @cancel="cancelDelete"
      />
    </div>
  </DashboardLayout>
</template>
