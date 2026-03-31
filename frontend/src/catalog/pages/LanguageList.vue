<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute } from 'vue-router';

import { useLanguageFiltering } from '@/catalog/composables/useLanguageFiltering';
import { useLanguageStore } from '@/catalog/stores/language';
import { useProjectStore } from '@/catalog/stores/project';
import Pagination from '@/shared/components/Pagination.vue';
import TechBadge from '@/shared/components/TechBadge.vue';
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue';

const route = useRoute();
const { t } = useI18n();
const languageStore = useLanguageStore();
const projectStore = useProjectStore();

const projectId = route.query.project_id as string | undefined;

const projectMap = computed(() => {
  const map = new Map<string, { name: string }>();
  for (const p of projectStore.projects) map.set(p.id, { name: p.name });
  return map;
});

const {
  availableLanguages,
  filterLanguage,
  filterStatus,
  groupedLanguages,
  search,
  sortIndicator,
  toggleSort,
} = useLanguageFiltering({ languages: computed(() => languageStore.languages), projectMap });

onMounted(async () => {
  await Promise.all([languageStore.fetchAll(1, 1000, projectId), projectStore.fetchAll(1, 200)]);
});

function changePage(page: number) {
  languageStore.fetchAll(page, 1000, projectId);
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="language-list-page">
      <nav
        class="mb-6 flex items-center gap-1 text-sm text-text-muted"
        data-testid="language-list-breadcrumb"
      >
        <span class="font-medium text-text">{{ t('catalog.languages.title') }}</span>
      </nav>

      <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text">
          {{ t('catalog.languages.title') }}
          <span v-if="groupedLanguages.length > 0" class="text-lg font-normal text-text-muted">
            ({{ languageStore.total }})
          </span>
        </h2>
      </div>

      <div
        v-if="languageStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="language-list-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="languageStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="language-list-error"
      >
        {{ languageStore.error }}
      </div>

      <template v-else>
        <div class="mb-4 flex flex-wrap items-center gap-3" data-testid="language-filters">
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
              v-model="search"
              type="search"
              :placeholder="t('catalog.languages.searchPlaceholder')"
              :aria-label="t('catalog.languages.searchPlaceholder')"
              class="w-full rounded-lg border border-border bg-surface py-2 pl-9 pr-3 text-sm text-text placeholder:text-text-muted focus:border-primary focus:outline-none"
              data-testid="language-search"
            />
          </div>
          <select
            v-model="filterLanguage"
            :aria-label="t('catalog.languages.allLanguages')"
            class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"
            data-testid="language-filter-name"
          >
            <option value="">{{ t('catalog.languages.allLanguages') }}</option>
            <option v-for="lang in availableLanguages" :key="lang" :value="lang">{{ lang }}</option>
          </select>
          <select
            v-model="filterStatus"
            :aria-label="t('catalog.languages.allStatuses')"
            class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"
            data-testid="language-filter-status"
          >
            <option value="">{{ t('catalog.languages.allStatuses') }}</option>
            <option value="active">{{ t('catalog.techStacks.statusActive') }}</option>
            <option value="eol">{{ t('catalog.techStacks.statusUnmaintained') }}</option>
          </select>
        </div>

        <div
          v-if="languageStore.languages.length > 0"
          class="overflow-hidden rounded-xl border border-border bg-surface"
        >
          <table class="w-full" data-testid="language-list-table">
            <thead>
              <tr class="border-b border-border bg-surface-muted">
                <th
                  class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
                  @click="toggleSort('project')"
                >
                  {{ t('catalog.languages.project') }}{{ sortIndicator('project') }}
                </th>
                <th
                  class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
                  @click="toggleSort('name')"
                >
                  {{ t('catalog.languages.language') }}{{ sortIndicator('name') }}
                </th>
                <th
                  class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
                  @click="toggleSort('version')"
                >
                  {{ t('catalog.languages.version') }}{{ sortIndicator('version') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('catalog.languages.eolDate') }}
                </th>
                <th
                  class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
                  @click="toggleSort('status')"
                >
                  {{ t('catalog.languages.status') }}{{ sortIndicator('status') }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in groupedLanguages"
                :key="row.lang.id"
                :class="[
                  row.isFirstInGroup ? 'border-t border-border first:border-0' : '',
                  row.groupIndex % 2 === 1 ? 'bg-surface-muted/50' : '',
                ]"
                data-testid="language-list-row"
              >
                <td
                  v-if="row.isFirstInGroup"
                  :rowspan="row.groupSize"
                  class="px-4 py-3 text-sm align-top"
                >
                  <RouterLink
                    :to="{ name: 'catalog-projects-detail', params: { id: row.projectId } }"
                    class="font-medium text-primary hover:text-primary-dark"
                  >
                    {{ row.projectName }}
                  </RouterLink>
                </td>
                <td class="px-4 py-3 text-sm text-text">
                  <TechBadge :name="row.lang.name" :version="row.lang.version" size="sm" />
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">{{ row.lang.version || '—' }}</td>
                <td class="px-4 py-3 text-sm text-text-muted">{{ row.lang.eolDate ?? '—' }}</td>
                <td class="px-4 py-3 text-sm">
                  <span
                    v-if="row.lang.maintenanceStatus === 'eol'"
                    class="rounded-full bg-danger/10 px-2 py-0.5 text-xs font-medium text-danger"
                    data-testid="language-eol-badge"
                  >
                    {{ t('catalog.techStacks.unmaintained') }}
                  </span>
                  <span
                    v-else-if="row.lang.maintenanceStatus === 'active'"
                    class="rounded-full bg-success/10 px-2 py-0.5 text-xs font-medium text-success"
                    data-testid="language-active-badge"
                  >
                    {{ t('catalog.techStacks.statusActive') }}
                  </span>
                  <span v-else class="text-text-muted">—</span>
                </td>
              </tr>
            </tbody>
          </table>
          <div
            v-if="groupedLanguages.length === 0"
            class="py-8 text-center text-text-muted"
            data-testid="language-list-no-match"
          >
            {{ t('catalog.languages.noMatchingLanguages') }}
          </div>
        </div>

        <div v-else class="overflow-hidden rounded-xl border border-border bg-surface">
          <div class="flex flex-col items-center py-12" data-testid="language-list-empty">
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
              {{ t('catalog.languages.noLanguages') }}
            </p>
            <p class="mb-4 text-sm text-text-muted">{{ t('catalog.languages.noLanguagesHint') }}</p>
            <RouterLink
              :to="{ name: 'catalog-providers-list' }"
              class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
              data-testid="language-empty-providers-link"
            >
              {{ t('catalog.providers.title') }}
            </RouterLink>
          </div>
        </div>

        <Pagination
          v-if="languageStore.totalPages > 1"
          :page="languageStore.currentPage"
          :total-pages="languageStore.totalPages"
          data-testid="language-list-pagination"
          @update:page="changePage"
        />
      </template>
    </div>
  </DashboardLayout>
</template>
