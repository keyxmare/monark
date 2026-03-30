<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';

import type { Dependency } from '@/dependency/types';

import { humanizeTimeDiff, ltsUrgency } from '@/catalog/composables/useFrameworkLts';
import { useProjectStore } from '@/catalog/stores/project';
import DependencyFilters from '@/dependency/components/DependencyFilters.vue';
import DependencyHealthScore from '@/dependency/components/DependencyHealthScore.vue';
import { useDependencyExport } from '@/dependency/composables/useDependencyExport';
import { useDependencyFilters } from '@/dependency/composables/useDependencyFilters';
import { useDependencyGrouping } from '@/dependency/composables/useDependencyGrouping';
import { useDependencyStats } from '@/dependency/composables/useDependencyStats';
import { useDependencySyncProgress } from '@/dependency/composables/useDependencySyncProgress';
import { dependencyService } from '@/dependency/services/dependency.service';
import { useDependencyStore } from '@/dependency/stores/dependency';
import ExportDropdown from '@/shared/components/ExportDropdown.vue';
import Pagination from '@/shared/components/Pagination.vue';
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue';
import { useToastStore } from '@/shared/stores/toast';

const { t } = useI18n();
const dependencyStore = useDependencyStore();
const projectStore = useProjectStore();
const toastStore = useToastStore();
const { track: trackSync } = useDependencySyncProgress();
const syncing = ref(false);

const allDeps = computed<Dependency[]>(() => {
  const raw = dependencyStore.dependencies;
  return Array.isArray(raw) ? raw : (raw?.value ?? []);
});

const projectMap = computed(() => {
  const map = new Map<string, string>();
  for (const p of projectStore.projects) map.set(p.id, p.name);
  return map;
});

function projectName(id: string) {
  return projectMap.value.get(id) ?? id;
}

const { filteredDeps, filters, sortDir, sortField, sortIndicator, toggleSort } =
  useDependencyFilters(allDeps, projectMap);

const { groupedDeps } = useDependencyGrouping(filteredDeps, projectName, sortField, sortDir);

const { depGapStats, healthScore, loadStats } = useDependencyStats(
  allDeps,
  filteredDeps,
  filters,
  projectName,
);

const { handleExport } = useDependencyExport(filteredDeps, projectName, healthScore, depGapStats);

onMounted(async () => {
  await Promise.all([dependencyStore.fetchAll(1, 1000), projectStore.fetchAll(1, 200)]);
  await loadStats();
});

function changePage(page: number) {
  dependencyStore.fetchAll(page, 1000);
}

async function handleDelete(id: string) {
  await dependencyStore.remove(id);
}

async function handleSync() {
  syncing.value = true;
  try {
    const response = await dependencyService.sync();
    trackSync(response.data.syncId, response.data.total);
  } catch {
    toastStore.addToast({ title: t('common.errors.failedToSync'), variant: 'error' });
  } finally {
    syncing.value = false;
  }
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="dependency-list-page">
      <nav class="mb-6 flex items-center gap-1 text-sm text-text-muted">
        <span class="font-medium text-text">{{ t('dependency.dependencies.title') }}</span>
      </nav>

      <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text">
          {{ t('dependency.dependencies.title') }}
        </h2>
        <div class="flex items-center gap-3">
          <ExportDropdown @export="handleExport" />
          <button
            :disabled="syncing"
            class="rounded-lg border border-primary bg-transparent px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary hover:text-white disabled:opacity-50"
            @click="handleSync"
          >
            {{
              syncing
                ? t('dependency.dependencies.syncing')
                : t('dependency.dependencies.syncVersions')
            }}
          </button>
        </div>
      </div>

      <div
        v-if="dependencyStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="dependency-list-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="dependencyStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="dependency-list-error"
      >
        {{ dependencyStore.error }}
      </div>

      <template v-else>
        <DependencyHealthScore :health-score="healthScore" :dep-gap-stats="depGapStats" />

        <DependencyFilters
          v-model:search="filters.search"
          v-model:filter-pm="filters.packageManager"
          v-model:filter-type="filters.type"
          v-model:filter-status="filters.status"
          v-model:filter-project="filters.projectId"
        />

        <div class="overflow-hidden rounded-xl border border-border bg-surface">
          <table class="w-full" data-testid="dependency-list-table">
            <thead>
              <tr class="border-b border-border bg-surface-muted">
                <th
                  class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
                  @click="toggleSort('name')"
                >
                  {{ t('dependency.dependencies.name') }}{{ sortIndicator('name') }}
                </th>
                <th
                  class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
                  @click="toggleSort('project')"
                >
                  {{ t('catalog.techStacks.project') }}{{ sortIndicator('project') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('dependency.dependencies.currentVersion') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('catalog.techStacks.ltsGap') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('dependency.dependencies.packageManager') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('dependency.dependencies.type') }}
                </th>
                <th
                  class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
                  @click="toggleSort('status')"
                >
                  {{ t('dependency.dependencies.status') }}{{ sortIndicator('status') }}
                </th>
                <th
                  class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
                  @click="toggleSort('vulnerabilities')"
                >
                  {{ t('dependency.dependencies.vulnerabilities')
                  }}{{ sortIndicator('vulnerabilities') }}
                </th>
                <th class="px-4 py-3 text-right text-sm font-medium text-text-muted">
                  {{ t('common.table.actions') }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in groupedDeps"
                :key="row.dep.id"
                :class="[
                  row.isFirstInGroup ? 'border-t border-border first:border-0' : '',
                  row.groupIndex % 2 === 1 ? 'bg-surface-muted/50' : '',
                ]"
                data-testid="dependency-list-row"
              >
                <td
                  v-if="row.isFirstInGroup"
                  :rowspan="row.groupSize"
                  class="px-4 py-3 text-sm align-top font-medium text-text"
                >
                  {{ row.dep.name }}
                </td>
                <td class="px-4 py-3 text-sm">
                  <RouterLink
                    :to="{ name: 'catalog-projects-detail', params: { id: row.projectId } }"
                    class="text-primary hover:text-primary-dark"
                  >
                    {{ projectName(row.projectId) }}
                  </RouterLink>
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">
                  <span class="inline-flex items-center gap-1.5">
                    {{ row.dep.currentVersion }}
                    <template v-if="row.dep.registryStatus === 'not_found'">
                      <span class="text-text-muted">→</span>
                      <span class="font-medium text-text-muted italic">{{
                        t('dependency.dependencies.unknown')
                      }}</span>
                    </template>
                    <template v-else-if="row.dep.registryStatus === 'pending'">
                      <span class="text-text-muted italic text-xs"
                        >({{ t('dependency.dependencies.pendingSync') }})</span
                      >
                    </template>
                    <template v-else-if="row.dep.isOutdated && row.dep.latestVersion">
                      <span class="text-text-muted">→</span>
                      <span class="font-medium text-success">{{ row.dep.latestVersion }}</span>
                    </template>
                  </span>
                </td>
                <td class="px-4 py-3 text-sm">
                  <span
                    v-if="row.dep.registryStatus === 'not_found'"
                    class="text-text-muted italic"
                    >{{ t('dependency.dependencies.unknown') }}</span
                  >
                  <span
                    v-if="row.dep.registryStatus === 'pending'"
                    class="text-text-muted italic"
                    >{{ t('dependency.dependencies.pendingSync') }}</span
                  >
                  <template v-else-if="row.dep.isOutdated">
                    <span
                      v-if="row.dep.currentVersionReleasedAt && row.dep.latestVersionReleasedAt"
                      :class="{
                        'text-success':
                          ltsUrgency(
                            row.dep.currentVersionReleasedAt,
                            row.dep.latestVersionReleasedAt,
                          ) === 'fresh',
                        'text-warning':
                          ltsUrgency(
                            row.dep.currentVersionReleasedAt,
                            row.dep.latestVersionReleasedAt,
                          ) === 'moderate',
                        'text-danger':
                          ltsUrgency(
                            row.dep.currentVersionReleasedAt,
                            row.dep.latestVersionReleasedAt,
                          ) === 'outdated',
                      }"
                    >
                      {{
                        humanizeTimeDiff(
                          row.dep.currentVersionReleasedAt,
                          row.dep.latestVersionReleasedAt,
                        )
                      }}
                    </span>
                    <span v-else class="text-warning">
                      {{ row.dep.currentVersion }} → {{ row.dep.latestVersion }}
                    </span>
                  </template>
                  <span v-else class="text-success">{{ t('catalog.techStacks.upToDate') }}</span>
                </td>
                <td class="px-4 py-3">
                  <span
                    class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800"
                  >
                    <img
                      v-if="row.dep.packageManager === 'npm'"
                      src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/npm/npm-original-wordmark.svg"
                      alt="npm"
                      class="h-3 w-3"
                    />
                    <img
                      v-else-if="row.dep.packageManager === 'composer'"
                      src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/composer/composer-original.svg"
                      alt="composer"
                      class="h-3 w-3"
                    />
                    {{ row.dep.packageManager }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span
                    :class="{
                      'bg-purple-100 text-purple-800': row.dep.type === 'runtime',
                      'bg-gray-100 text-gray-800': row.dep.type === 'dev',
                    }"
                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                  >
                    {{ row.dep.type }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span
                    v-if="row.dep.registryStatus === 'not_found'"
                    class="rounded-full bg-gray-800 px-2 py-0.5 text-xs font-medium text-white"
                  >
                    {{ t('dependency.dependencies.dead') }}
                  </span>
                  <span
                    v-else-if="row.dep.registryStatus === 'pending'"
                    class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600"
                  >
                    {{ t('dependency.dependencies.pendingSync') }}
                  </span>
                  <span
                    v-else
                    :class="{
                      'bg-red-100 text-red-800': row.dep.isOutdated,
                      'bg-green-100 text-green-800': !row.dep.isOutdated,
                    }"
                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                  >
                    {{
                      row.dep.isOutdated
                        ? t('dependency.dependencies.outdated')
                        : t('dependency.dependencies.upToDate')
                    }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm">
                  <span
                    :class="{
                      'bg-gray-100 text-gray-600': row.dep.vulnerabilityCount === 0,
                      'bg-orange-100 text-orange-800':
                        row.dep.vulnerabilityCount > 0 && row.dep.vulnerabilityCount <= 3,
                      'bg-red-100 text-red-800': row.dep.vulnerabilityCount > 3,
                    }"
                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                  >
                    {{ row.dep.vulnerabilityCount }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right">
                  <RouterLink
                    :to="{ name: 'dependency-dependencies-detail', params: { id: row.dep.id } }"
                    class="text-sm text-primary hover:text-primary-dark"
                  >
                    {{ t('common.actions.view') }}
                  </RouterLink>
                </td>
              </tr>
            </tbody>
          </table>

          <div
            v-if="allDeps.length === 0"
            class="flex flex-col items-center py-12"
            data-testid="dependency-list-empty"
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
                d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"
              />
            </svg>
            <p class="mb-1 text-sm font-medium text-text">
              {{ t('dependency.dependencies.noDependencies') }}
            </p>
            <p class="text-sm text-text-muted">
              {{ t('catalog.projects.noDependencies') }}
            </p>
          </div>
        </div>
      </template>
      <Pagination
        v-if="dependencyStore.totalPages > 1"
        :page="dependencyStore.currentPage"
        :total-pages="dependencyStore.totalPages"
        data-testid="dependency-list-pagination"
        @update:page="changePage"
      />
    </div>
  </DashboardLayout>
</template>
