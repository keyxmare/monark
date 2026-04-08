<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';

import { useDependencyStore } from '@/dependency/stores/dependency';
import Pagination from '@/shared/components/Pagination.vue';

const PER_PAGE = 20;

const props = defineProps<{ projectId: string }>();

const { t } = useI18n();
const dependencyStore = useDependencyStore();
const depSearch = ref('');
const depFilterPm = ref('all');
const depTypeFilter = ref('');

const filteredDependencies = computed(() => {
  let deps = dependencyStore.dependencies;
  if (depSearch.value.trim()) {
    const q = depSearch.value.toLowerCase();
    deps = deps.filter((dep) => dep.name.toLowerCase().includes(q));
  }
  if (depFilterPm.value !== 'all') {
    deps = deps.filter((dep) => dep.packageManager === depFilterPm.value);
  }
  if (depTypeFilter.value) {
    deps = deps.filter((dep) => dep.type === depTypeFilter.value);
  }
  return deps;
});

function truncateUrl(url: string, max = 50): string {
  if (url.length <= max) return url;
  return `${url.slice(0, max)}…`;
}

onMounted(() => {
  dependencyStore.fetchAll(1, PER_PAGE, props.projectId);
});

function changeDependencyPage(page: number) {
  dependencyStore.fetchAll(page, PER_PAGE, props.projectId);
}
</script>

<template>
  <div data-testid="dependencies-panel">
    <div class="mb-4 flex flex-wrap items-center gap-3" data-testid="dependencies-filters">
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
        />
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
        <option value="composer">Composer</option>
        <option value="npm">npm</option>
        <option value="pip">pip</option>
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
            <td class="px-4 py-3 text-sm">
              <RouterLink
                :to="{ name: 'dependency-dependencies-detail', params: { id: dep.id } }"
                class="font-medium text-primary hover:text-primary-dark"
              >
                {{ dep.name }}
              </RouterLink>
            </td>
            <td class="px-4 py-3 text-sm text-text-muted">
              {{ dep.currentVersion }}
            </td>
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center gap-1 rounded-full bg-info/10 px-2 py-0.5 text-xs font-medium text-info"
              >
                <img
                  v-if="dep.packageManager === 'npm'"
                  src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/npm/npm-original-wordmark.svg"
                  alt="npm"
                  class="h-3 w-3"
                />
                <img
                  v-else-if="dep.packageManager === 'composer'"
                  src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/composer/composer-original.svg"
                  alt="composer"
                  class="h-3 w-3"
                />
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
                >{{ truncateUrl(dep.repositoryUrl, 35) }} ↗</a
              >
              <span v-else class="text-text-muted">—</span>
            </td>
          </tr>
        </tbody>
      </table>
      <div
        v-if="filteredDependencies.length === 0"
        class="py-8 text-center text-text-muted"
        data-testid="dependencies-empty"
      >
        {{
          depSearch || depFilterPm !== 'all' || depTypeFilter
            ? t('catalog.projects.noMatchingDependencies')
            : t('catalog.projects.noDependencies')
        }}
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
</template>
