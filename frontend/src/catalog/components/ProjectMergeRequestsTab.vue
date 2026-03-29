<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

import { useMergeRequestStore } from '@/catalog/stores/merge-request';
import Pagination from '@/shared/components/Pagination.vue';

const PER_PAGE = 20;

const props = defineProps<{ projectId: string }>();

const { d, t } = useI18n();
const mergeRequestStore = useMergeRequestStore();
const mrStatusFilter = ref('');
const mrAuthorSearch = ref('');

const filteredMergeRequests = computed(() => {
  return mergeRequestStore.mergeRequests.filter((mr) => {
    if (mrStatusFilter.value && mr.status !== mrStatusFilter.value) return false;
    if (
      mrAuthorSearch.value &&
      !mr.author.toLowerCase().includes(mrAuthorSearch.value.toLowerCase())
    )
      return false;
    return true;
  });
});

onMounted(() => {
  mergeRequestStore.fetchAll(props.projectId, 1, PER_PAGE, 'active');
});

function changeMergeRequestPage(page: number) {
  mergeRequestStore.fetchAll(props.projectId, page, PER_PAGE, 'active');
}
</script>

<template>
  <div data-testid="merge-requests-panel">
    <div class="mb-4 flex flex-wrap items-center gap-3" data-testid="merge-requests-filters">
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
        />
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
        {{
          mrStatusFilter || mrAuthorSearch
            ? t('catalog.projects.noMatchingMergeRequests')
            : t('catalog.mergeRequests.noMergeRequests')
        }}
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
