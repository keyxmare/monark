<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute } from 'vue-router';

import ProviderIcon from '@/catalog/components/ProviderIcon.vue';
import { useFrameworkGrouping } from '@/catalog/composables/useFrameworkGrouping';
import { useSyncProgress } from '@/catalog/composables/useSyncProgress';
import { useFrameworkStore } from '@/catalog/stores/framework';
import { useProjectStore } from '@/catalog/stores/project';
import { useProviderStore } from '@/catalog/stores/provider';
import ExportDropdown from '@/shared/components/ExportDropdown.vue';
import Pagination from '@/shared/components/Pagination.vue';
import TechBadge from '@/shared/components/TechBadge.vue';
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue';
import { formatRelative } from '@/shared/utils/dateFormat';

const route = useRoute();
const { t } = useI18n();
const frameworkStore = useFrameworkStore();
const projectStore = useProjectStore();
const providerStore = useProviderStore();
const { track } = useSyncProgress();
const syncing = ref(false);

const projectId = route.query.project_id as string | undefined;

const projectMap = computed(() => {
  const map = new Map<string, { name: string; providerId: null | string }>();
  for (const p of projectStore.projects) map.set(p.id, { name: p.name, providerId: p.providerId });
  return map;
});

const providerMap = computed(() => {
  const map = new Map<string, { name: string; type: string }>();
  for (const p of providerStore.providers) map.set(p.id, { name: p.name, type: p.type });
  return map;
});

const {
  availableFrameworks,
  availableProviders,
  filterFramework,
  filterProvider,
  filterStatus,
  filteredFrameworks,
  groupBy,
  groupedFrameworks,
  healthScore,
  providerAggregates,
  search,
  sortIndicator,
  toggleSort,
} = useFrameworkGrouping({
  frameworks: computed(() => frameworkStore.frameworks),
  projectMap,
  providerMap,
});

onMounted(async () => {
  await Promise.all([
    frameworkStore.fetchAll(1, 1000, projectId),
    projectStore.fetchAll(1, 200),
    providerStore.fetchAll(1, 50),
  ]);
});

function changePage(page: number) {
  frameworkStore.fetchAll(page, 1000, projectId);
}

function exportCsv() {
  const headers = ['Projet', 'Framework', 'Version', 'Dernière LTS', 'Écart LTS', 'Statut'];
  const rows = filteredFrameworks.value.map((fw) => {
    const projName = projectMap.value.get(fw.projectId)?.name ?? fw.projectId;
    const statusLabel = fw.maintenanceStatus === 'eol' ? 'Non maintenu' : fw.maintenanceStatus === 'warning' ? 'Inactif' : 'OK';
    return [projName, fw.name, fw.version, fw.latestLts ?? '', fw.ltsGap ?? '', statusLabel];
  });
  const csv = [headers, ...rows].map((r) => r.map((c) => `"${c}"`).join(',')).join('\n');
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'frameworks.csv';
  a.click();
  URL.revokeObjectURL(url);
}

function handleExport(format: 'csv' | 'pdf') {
  if (format === 'csv') exportCsv();
}

async function handleSyncAll() {
  syncing.value = true;
  try {
    const result = await providerStore.syncAllGlobal();
    track(result.id, result.projectsCount);
  } catch {
  } finally {
    syncing.value = false;
  }
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="framework-list-page">
      <nav class="mb-6 flex items-center gap-1 text-sm text-text-muted" data-testid="framework-list-breadcrumb">
        <span class="font-medium text-text">{{ t('catalog.frameworks.title') }}</span>
      </nav>

      <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text">
          {{ t('catalog.frameworks.title') }}
          <span v-if="filteredFrameworks.length > 0" class="text-lg font-normal text-text-muted">({{ filteredFrameworks.length }})</span>
        </h2>
        <div class="flex items-center gap-3">
          <ExportDropdown @export="handleExport" />
          <button :disabled="syncing" class="rounded-lg border border-primary bg-transparent px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary hover:text-white disabled:opacity-50" data-testid="framework-sync-all" @click="handleSyncAll">
            {{ syncing ? t('catalog.providers.syncing') : t('catalog.providers.syncAll') }}
          </button>
        </div>
      </div>

      <div v-if="frameworkStore.loading" class="py-8 text-center text-text-muted" data-testid="framework-list-loading">
        {{ t('common.actions.loading') }}
      </div>

      <div v-else-if="frameworkStore.error" class="rounded-lg bg-danger/10 p-4 text-danger" role="alert" data-testid="framework-list-error">
        {{ frameworkStore.error }}
      </div>

      <template v-else>
        <div v-if="healthScore" class="mb-6 flex flex-wrap items-center gap-4 rounded-xl border border-border bg-surface p-4" data-testid="framework-health-score">
          <div class="flex-1">
            <div class="mb-1 flex items-center justify-between text-sm">
              <span class="font-medium text-text">{{ t('catalog.techStacks.healthScore', { percent: healthScore.percent }) }}</span>
              <span class="text-text-muted">{{ healthScore.active }}/{{ healthScore.total }}</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-surface-muted">
              <div class="h-full rounded-full bg-success transition-all" :style="{ width: `${healthScore.percent}%` }" />
            </div>
          </div>
          <div v-if="healthScore.eol > 0" class="rounded-full bg-danger/10 px-3 py-1 text-sm font-medium text-danger">
            {{ t('catalog.techStacks.healthEol', { count: healthScore.eol }) }}
          </div>
          <div v-if="healthScore.warning > 0" class="rounded-full bg-warning/10 px-3 py-1 text-sm font-medium text-warning">
            {{ t('catalog.techStacks.healthWarning', { count: healthScore.warning }) }}
          </div>
        </div>

        <div v-if="providerAggregates.length > 0" class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" data-testid="framework-provider-aggregates">
          <div v-for="agg in providerAggregates" :key="agg.name" class="rounded-xl border border-border bg-surface p-4" data-testid="framework-provider-card">
            <div class="mb-3 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <ProviderIcon :type="agg.type as any" :size="20" />
                <RouterLink :to="{ name: 'catalog-providers-detail', params: { id: agg.id } }" class="text-sm font-semibold text-primary hover:text-primary-dark">
                  {{ agg.name }}
                </RouterLink>
              </div>
              <span class="text-xs text-text-muted">{{ t('catalog.techStacks.projectCount', { count: agg.projectCount }) }}</span>
            </div>
            <div class="space-y-1.5">
              <div v-for="fw in agg.frameworks" :key="fw.name" class="flex items-center justify-between text-sm">
                <span class="font-medium text-text">{{ fw.name }}</span>
                <span class="tabular-nums text-text-muted">
                  <template v-if="fw.min === fw.max">{{ fw.min }}</template>
                  <template v-else>{{ fw.min }} → {{ fw.max }}</template>
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="mb-4 flex flex-wrap items-center gap-3" data-testid="framework-filters">
          <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <input v-model="search" type="search" :placeholder="t('catalog.frameworks.searchPlaceholder')" :aria-label="t('catalog.frameworks.searchPlaceholder')" class="w-full rounded-lg border border-border bg-surface py-2 pl-9 pr-3 text-sm text-text placeholder:text-text-muted focus:border-primary focus:outline-none" data-testid="framework-search" />
          </div>
          <select v-model="filterFramework" :aria-label="t('catalog.frameworks.allFrameworks')" class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none" data-testid="framework-filter-name">
            <option value="">{{ t('catalog.frameworks.allFrameworks') }}</option>
            <option v-for="fw in availableFrameworks" :key="fw" :value="fw">{{ fw }}</option>
          </select>
          <select v-model="filterProvider" :aria-label="t('catalog.techStacks.allProviders')" class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none" data-testid="framework-filter-provider">
            <option value="">{{ t('catalog.techStacks.allProviders') }}</option>
            <option v-for="prov in availableProviders" :key="prov.id" :value="prov.id">{{ prov.name }}</option>
          </select>
          <select v-model="filterStatus" :aria-label="t('catalog.techStacks.allStatuses')" class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none" data-testid="framework-filter-status">
            <option value="">{{ t('catalog.techStacks.allStatuses') }}</option>
            <option value="active">{{ t('catalog.techStacks.statusActive') }}</option>
            <option value="warning">{{ t('catalog.techStacks.statusInactive') }}</option>
            <option value="eol">{{ t('catalog.techStacks.statusUnmaintained') }}</option>
          </select>
        </div>

        <div class="mb-4 flex items-center gap-1" data-testid="framework-group-toggle">
          <button
            v-for="mode in (['project', 'framework', 'provider'] as const)"
            :key="mode"
            :class="groupBy === mode ? 'border-primary bg-primary/10 text-primary' : 'border-border text-text-muted hover:border-primary/50'"
            class="rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors"
            @click="groupBy = mode"
          >
            {{ t(`catalog.techStacks.groupBy${mode.charAt(0).toUpperCase() + mode.slice(1)}`) }}
          </button>
        </div>

        <div v-if="frameworkStore.frameworks.length > 0" class="overflow-hidden rounded-xl border border-border bg-surface">
          <table class="w-full" data-testid="framework-list-table">
            <thead>
              <tr class="border-b border-border bg-surface-muted">
                <th class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text" @click="toggleSort('project')">
                  {{ t('catalog.frameworks.project') }}{{ sortIndicator('project') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">{{ t('catalog.frameworks.language') }}</th>
                <th class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text" @click="toggleSort('framework')">
                  {{ t('catalog.frameworks.framework') }}{{ sortIndicator('framework') }}
                </th>
                <th class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text" @click="toggleSort('version')">
                  {{ t('catalog.frameworks.version') }}{{ sortIndicator('version') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">{{ t('catalog.techStacks.latestLts') }}</th>
                <th class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text" @click="toggleSort('ltsGap')">
                  {{ t('catalog.techStacks.ltsGap') }}{{ sortIndicator('ltsGap') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">{{ t('catalog.techStacks.syncedAt') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in groupedFrameworks"
                :key="row.fw.id"
                :class="[row.isFirstInGroup ? 'border-t border-border first:border-0' : '', row.groupIndex % 2 === 1 ? 'bg-surface-muted/50' : '']"
                data-testid="framework-list-row"
              >
                <td v-if="row.isFirstInGroup" :rowspan="row.groupSize" class="px-4 py-3 text-sm align-top">
                  <RouterLink v-if="groupBy === 'project'" :to="{ name: 'catalog-projects-detail', params: { id: row.projectId } }" class="font-medium text-primary hover:text-primary-dark">
                    {{ row.projectName }}
                  </RouterLink>
                  <span v-else class="font-medium text-text">{{ row.projectName }}</span>
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">—</td>
                <td class="px-4 py-3 text-sm text-text">
                  <TechBadge :name="row.fw.name" :version="row.fw.version" size="sm" />
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">
                  <span class="inline-flex items-center gap-1.5">
                    {{ row.fw.version || '—' }}
                    <span v-if="row.fw.maintenanceStatus === 'eol'" class="rounded-full bg-danger/10 px-1.5 py-0.5 text-xs font-medium text-danger" :title="row.fw.eolDate ? t('catalog.techStacks.unmaintainedSince', { date: row.fw.eolDate }) : t('catalog.techStacks.unmaintainedNoDate')" data-testid="framework-eol-badge">
                      {{ t('catalog.techStacks.unmaintained') }}
                    </span>
                    <span v-else-if="row.fw.maintenanceStatus === 'warning'" class="rounded-full bg-warning/10 px-1.5 py-0.5 text-xs font-medium text-warning" data-testid="framework-warning-badge">
                      {{ t('catalog.techStacks.inactive') }}
                    </span>
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">{{ row.fw.latestLts ?? '—' }}</td>
                <td class="px-4 py-3 text-sm">
                  <span v-if="row.fw.ltsGap" :class="{ 'text-success': row.fw.maintenanceStatus === 'active', 'text-warning': row.fw.maintenanceStatus === 'warning', 'text-danger': row.fw.maintenanceStatus === 'eol' }">
                    {{ row.fw.ltsGap }}
                  </span>
                  <span v-else class="text-text-muted">—</span>
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">
                  {{ row.fw.versionSyncedAt ? formatRelative(row.fw.versionSyncedAt) : '—' }}
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="groupedFrameworks.length === 0" class="py-8 text-center text-text-muted" data-testid="framework-list-no-match">
            {{ t('catalog.frameworks.noMatchingFrameworks') }}
          </div>
        </div>

        <div v-else class="overflow-hidden rounded-xl border border-border bg-surface">
          <div class="flex flex-col items-center py-12" data-testid="framework-list-empty">
            <svg class="mb-4 h-12 w-12 text-text-muted/50" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0L12 16.5l-5.571-2.25m11.142 0L21.75 16.5 12 21.75 2.25 16.5l4.179-2.25" />
            </svg>
            <p class="mb-1 text-sm font-medium text-text">{{ t('catalog.frameworks.noFrameworks') }}</p>
            <p class="mb-4 text-sm text-text-muted">{{ t('catalog.frameworks.noFrameworksHint') }}</p>
            <RouterLink :to="{ name: 'catalog-providers-list' }" class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark" data-testid="framework-empty-providers-link">
              {{ t('catalog.providers.title') }}
            </RouterLink>
          </div>
        </div>

        <Pagination v-if="frameworkStore.totalPages > 1" :page="frameworkStore.currentPage" :total-pages="frameworkStore.totalPages" data-testid="framework-list-pagination" @update:page="changePage" />
      </template>
    </div>
  </DashboardLayout>
</template>
