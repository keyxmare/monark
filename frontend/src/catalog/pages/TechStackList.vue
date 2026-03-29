<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute } from 'vue-router';

import ProviderIcon from '@/catalog/components/ProviderIcon.vue';
import TechStackFilters from '@/catalog/components/TechStackFilters.vue';
import TechStackTable from '@/catalog/components/TechStackTable.vue';
import { useSyncProgress } from '@/catalog/composables/useSyncProgress';
import { useTechStackGrouping } from '@/catalog/composables/useTechStackGrouping';
import { exportTechStacksPdf } from '@/catalog/services/techStackPdfExport';
import { useProjectStore } from '@/catalog/stores/project';
import { useProviderStore } from '@/catalog/stores/provider';
import { useTechStackStore } from '@/catalog/stores/tech-stack';
import ExportDropdown from '@/shared/components/ExportDropdown.vue';
import Pagination from '@/shared/components/Pagination.vue';
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue';

const route = useRoute();
const { t } = useI18n();
const techStackStore = useTechStackStore();
const projectStore = useProjectStore();
const providerStore = useProviderStore();
const { track } = useSyncProgress();
const syncing = ref(false);

const projectId = route.query.project_id as string | undefined;

const projectMap = computed(() => {
  const map = new Map<string, { name: string; providerId: null | string }>();
  for (const p of projectStore.projects) {
    map.set(p.id, { name: p.name, providerId: p.providerId });
  }
  return map;
});

const providerMap = computed(() => {
  const map = new Map<string, { name: string; type: string }>();
  for (const p of providerStore.providers) {
    map.set(p.id, { name: p.name, type: p.type });
  }
  return map;
});

const techStacks = computed(() => techStackStore.techStacks);

const {
  availableFrameworks,
  availableLanguages,
  availableProviders,
  filteredStacks,
  filterFramework,
  filterLanguage,
  filterProvider,
  filterStatus,
  groupBy,
  groupedStacks,
  healthScore,
  providerAggregates,
  search,
  sortIndicator,
  toggleSort,
  viewMode,
} = useTechStackGrouping({
  projectMap,
  providerMap,
  techStacks,
});

onMounted(async () => {
  await Promise.all([
    techStackStore.fetchAll(1, 1000, projectId),
    projectStore.fetchAll(1, 200),
    providerStore.fetchAll(1, 50),
  ]);
});

function changePage(page: number) {
  techStackStore.fetchAll(page, 1000, projectId);
}

function exportCsv() {
  const headers = [
    'Projet',
    'Langage',
    'Framework',
    'Version',
    'Dernière LTS',
    'Écart LTS',
    'Statut',
  ];
  const rows = filteredStacks.value.map((ts) => {
    const projName = projectMap.value.get(ts.projectId)?.name ?? ts.projectId;
    const lts = ts.latestLts ?? '';
    const gap = ts.ltsGap ?? '';
    const statusLabel =
      ts.maintenanceStatus === 'eol'
        ? 'Non maintenu'
        : ts.maintenanceStatus === 'warning'
          ? 'Inactif'
          : 'OK';
    return [projName, ts.language, ts.framework, ts.frameworkVersion, lts, gap, statusLabel];
  });

  const csv = [headers, ...rows].map((r) => r.map((c) => `"${c}"`).join(',')).join('\n');
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'tech-stacks.csv';
  a.click();
  URL.revokeObjectURL(url);
}

function exportPdf() {
  const rows = filteredStacks.value.map((ts) => {
    const projName = projectMap.value.get(ts.projectId)?.name ?? ts.projectId;
    const lts = ts.latestLts ?? '';
    const gap = ts.ltsGap ?? '—';
    let status = 'OK';

    if (ts.maintenanceStatus === 'eol') status = 'Non maintenu';
    else if (ts.maintenanceStatus === 'warning') status = 'Inactif';

    return {
      framework: ts.framework,
      language: ts.language,
      latestLts: lts,
      ltsGap: gap,
      project: projName,
      releaseDate: ts.versionSyncedAt ?? '',
      status,
      version: ts.frameworkVersion,
    };
  });

  exportTechStacksPdf(rows, healthScore.value, providerAggregates.value, null);
}

function handleExport(format: 'csv' | 'pdf') {
  if (format === 'csv') exportCsv();
  else exportPdf();
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
    <div data-testid="tech-stack-list-page">
      <nav
        class="mb-6 flex items-center gap-1 text-sm text-text-muted"
        data-testid="tech-stack-list-breadcrumb"
      >
        <span class="font-medium text-text">
          {{ t('catalog.techStacks.title') }}
        </span>
      </nav>

      <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text">
          {{ t('catalog.techStacks.title') }}
          <span v-if="filteredStacks.length > 0" class="text-lg font-normal text-text-muted"
            >({{ filteredStacks.length }})</span
          >
        </h2>
        <div class="flex items-center gap-3">
          <ExportDropdown @export="handleExport" />
          <button
            :disabled="syncing"
            class="rounded-lg border border-primary bg-transparent px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary hover:text-white disabled:opacity-50"
            data-testid="tech-stack-sync-all"
            @click="handleSyncAll"
          >
            {{ syncing ? t('catalog.providers.syncing') : t('catalog.providers.syncAll') }}
          </button>
        </div>
      </div>

      <!-- View mode tabs -->
      <div class="mb-6 flex border-b border-border" data-testid="tech-stack-view-tabs">
        <button
          v-for="mode in ['languages', 'frameworks'] as const"
          :key="mode"
          :class="
            viewMode === mode
              ? 'border-b-2 border-primary text-primary'
              : 'border-b-2 border-transparent text-text-muted hover:text-text'
          "
          class="px-4 py-2 text-sm font-medium transition-colors"
          :data-testid="`tech-stack-tab-${mode}`"
          @click="viewMode = mode"
        >
          {{ t(`catalog.techStacks.tab${mode.charAt(0).toUpperCase() + mode.slice(1)}`) }}
        </button>
      </div>

      <div
        v-if="techStackStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="tech-stack-list-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="techStackStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="tech-stack-list-error"
      >
        {{ techStackStore.error }}
      </div>

      <template v-else>
        <!-- Health score -->
        <div
          v-if="healthScore"
          class="mb-6 flex flex-wrap items-center gap-4 rounded-xl border border-border bg-surface p-4"
          data-testid="health-score"
        >
          <div class="flex-1">
            <div class="mb-1 flex items-center justify-between text-sm">
              <span class="font-medium text-text">{{
                t('catalog.techStacks.healthScore', { percent: healthScore.percent })
              }}</span>
              <span class="text-text-muted">{{ healthScore.active }}/{{ healthScore.total }}</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-surface-muted">
              <div
                class="h-full rounded-full bg-success transition-all"
                :style="{ width: `${healthScore.percent}%` }"
              />
            </div>
          </div>
          <div
            v-if="healthScore.eol > 0"
            class="rounded-full bg-danger/10 px-3 py-1 text-sm font-medium text-danger"
          >
            {{ t('catalog.techStacks.healthEol', { count: healthScore.eol }) }}
          </div>
          <div
            v-if="healthScore.warning > 0"
            class="rounded-full bg-warning/10 px-3 py-1 text-sm font-medium text-warning"
          >
            {{ t('catalog.techStacks.healthWarning', { count: healthScore.warning }) }}
          </div>
        </div>

        <!-- Provider aggregates -->
        <div
          v-if="providerAggregates.length > 0"
          class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
          data-testid="provider-aggregates"
        >
          <div
            v-for="agg in providerAggregates"
            :key="agg.name"
            class="rounded-xl border border-border bg-surface p-4"
            data-testid="provider-aggregate-card"
          >
            <div class="mb-3 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <ProviderIcon :type="agg.type as any" :size="20" />
                <RouterLink
                  :to="{ name: 'catalog-providers-detail', params: { id: agg.id } }"
                  class="text-sm font-semibold text-primary hover:text-primary-dark"
                >
                  {{ agg.name }}
                </RouterLink>
              </div>
              <span class="text-xs text-text-muted">
                {{ t('catalog.techStacks.projectCount', { count: agg.projectCount }) }}
              </span>
            </div>
            <div class="space-y-1.5">
              <div
                v-for="fw in agg.frameworks"
                :key="fw.name"
                class="flex items-center justify-between text-sm"
              >
                <span class="font-medium text-text">{{ fw.name }}</span>
                <span class="inline-flex items-center gap-1.5 tabular-nums text-text-muted">
                  <template v-if="fw.min === fw.max">
                    {{ fw.min }}
                  </template>
                  <template v-else> {{ fw.min }} → {{ fw.max }} </template>
                </span>
              </div>
            </div>
          </div>
        </div>

        <TechStackFilters
          v-model:search="search"
          v-model:filter-framework="filterFramework"
          v-model:filter-language="filterLanguage"
          v-model:filter-provider="filterProvider"
          v-model:filter-status="filterStatus"
          v-model:group-by="groupBy"
          :available-frameworks="availableFrameworks"
          :available-languages="availableLanguages"
          :available-providers="availableProviders"
          :view-mode="viewMode"
        />

        <TechStackTable
          v-if="techStackStore.techStacks.length > 0"
          :group-by="groupBy"
          :grouped-stacks="groupedStacks"
          :sort-indicator="sortIndicator"
          :view-mode="viewMode"
          @sort="toggleSort"
        />

        <div v-else class="overflow-hidden rounded-xl border border-border bg-surface">
          <div class="flex flex-col items-center py-12" data-testid="tech-stack-list-empty">
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
                d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0L12 16.5l-5.571-2.25m11.142 0L21.75 16.5 12 21.75 2.25 16.5l4.179-2.25"
              />
            </svg>
            <p class="mb-1 text-sm font-medium text-text">
              {{ t('catalog.techStacks.noTechStacks') }}
            </p>
            <p class="mb-4 text-sm text-text-muted">
              {{ t('catalog.techStacks.noTechStacksHint') }}
            </p>
            <RouterLink
              :to="{ name: 'catalog-providers-list' }"
              class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
              data-testid="tech-stack-empty-providers-link"
            >
              {{ t('catalog.providers.title') }}
            </RouterLink>
          </div>
        </div>

        <Pagination
          v-if="techStackStore.totalPages > 1"
          :page="techStackStore.currentPage"
          :total-pages="techStackStore.totalPages"
          data-testid="tech-stack-list-pagination"
          @update:page="changePage"
        />
      </template>
    </div>
  </DashboardLayout>
</template>
