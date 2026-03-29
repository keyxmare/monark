<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';

import { useMergeRequestStore } from '@/catalog/stores/merge-request';
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue';
import { MergeRequestState } from '@/shared/types/enums';

const route = useRoute();
const { d, t } = useI18n();
const store = useMergeRequestStore();

const projectId = computed(() => route.params.projectId as string);
const STORAGE_KEY = 'monark:mr-status-filter';
const savedFilter = localStorage.getItem(STORAGE_KEY) ?? 'active';
const statusFilter = ref<string>(savedFilter);
const authorFilter = ref('');

const statusOptions = [
  { label: t('catalog.mergeRequests.statusActive'), value: 'active' },
  { label: t('catalog.mergeRequests.statusOpen'), value: MergeRequestState.Open },
  { label: t('catalog.mergeRequests.statusDraft'), value: MergeRequestState.Draft },
  { label: t('catalog.mergeRequests.statusMerged'), value: MergeRequestState.Merged },
  { label: t('catalog.mergeRequests.statusClosed'), value: MergeRequestState.Closed },
  { label: t('catalog.mergeRequests.allStatuses'), value: '' },
];

onMounted(() => {
  store.fetchAll(
    projectId.value,
    1,
    20,
    statusFilter.value || undefined,
    authorFilter.value || undefined,
  );
});

watch([statusFilter, authorFilter], () => {
  localStorage.setItem(STORAGE_KEY, statusFilter.value);
  store.fetchAll(
    projectId.value,
    1,
    20,
    statusFilter.value || undefined,
    authorFilter.value || undefined,
  );
});

const statusBadgeClass: Record<string, string> = {
  closed: 'bg-danger/10 text-danger',
  draft: 'bg-warning/10 text-warning',
  merged: 'bg-info/10 text-info',
  open: 'bg-success/10 text-success',
};
</script>

<template>
  <DashboardLayout>
    <div data-testid="merge-request-list-page">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-text">
          {{ t('catalog.mergeRequests.title') }}
        </h1>
      </div>

      <div class="mb-4 flex items-center gap-4">
        <div class="flex rounded-lg border border-border" data-testid="mr-status-filter">
          <button
            v-for="opt in statusOptions"
            :key="opt.value"
            :class="[
              'px-3 py-1.5 text-sm font-medium transition-colors first:rounded-l-lg last:rounded-r-lg',
              statusFilter === opt.value
                ? 'bg-primary text-white'
                : 'bg-surface text-text-muted hover:text-text',
            ]"
            :data-testid="`mr-filter-${opt.value || 'all'}`"
            @click="statusFilter = opt.value"
          >
            {{ opt.label }}
          </button>
        </div>
        <input
          v-model="authorFilter"
          :placeholder="t('catalog.mergeRequests.filterByAuthor')"
          class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text"
          data-testid="mr-author-filter"
        />
      </div>

      <div v-if="store.loading" class="py-8 text-center text-text-muted" data-testid="mr-loading">
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="store.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="mr-error"
      >
        {{ store.error }}
      </div>

      <div v-else class="overflow-hidden rounded-xl border border-border bg-surface">
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
                {{ t('catalog.mergeRequests.changes') }}
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
              v-for="mr in store.mergeRequests"
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
                    statusBadgeClass[mr.status],
                  ]"
                  data-testid="mr-status-badge"
                >
                  {{
                    t(
                      `catalog.mergeRequests.status${mr.status.charAt(0).toUpperCase() + mr.status.slice(1)}`,
                    )
                  }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-text-muted">
                {{ mr.author }}
              </td>
              <td class="px-4 py-3 text-sm text-text-muted">
                {{ mr.sourceBranch }} → {{ mr.targetBranch }}
              </td>
              <td class="px-4 py-3 text-sm">
                <span v-if="mr.additions !== null" class="text-success">+{{ mr.additions }}</span>
                <span v-if="mr.deletions !== null" class="ml-1 text-danger"
                  >-{{ mr.deletions }}</span
                >
                <span v-if="mr.additions === null && mr.deletions === null" class="text-text-muted"
                  >—</span
                >
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
          v-if="store.mergeRequests.length === 0"
          class="py-8 text-center text-text-muted"
          data-testid="mr-empty"
        >
          {{ t('catalog.mergeRequests.noMergeRequests') }}
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
