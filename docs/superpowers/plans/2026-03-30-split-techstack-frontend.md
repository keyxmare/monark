# Plan: Split TechStack → Language + Framework (Frontend)

**Date**: 2026-03-30
**Goal**: Replace the single TechStack module with two distinct entities (Language, Framework) — two dedicated pages, separate services/stores, updated navigation.
**Architecture**: Vue 3 Composition API, Pinia, vue-i18n, Vitest
**Runtime**: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm ...`

---

## Task 1 — Types

**Files**
- CREATE `frontend/src/catalog/types/language.ts`
- CREATE `frontend/src/catalog/types/framework.ts`

### Steps

- [ ] Create `language.ts`

```ts
export interface Language {
  id: string;
  name: string;
  version: string;
  detectedAt: string;
  eolDate: string | null;
  maintenanceStatus: 'active' | 'eol' | null;
  projectId: string;
  createdAt: string;
  updatedAt: string;
}

export interface CreateLanguageInput {
  name: string;
  version: string;
  detectedAt: string;
  projectId: string;
}
```

- [ ] Create `framework.ts`

```ts
export interface Framework {
  id: string;
  name: string;
  version: string;
  detectedAt: string;
  latestLts: string | null;
  ltsGap: string | null;
  maintenanceStatus: 'active' | 'warning' | 'eol' | null;
  eolDate: string | null;
  versionSyncedAt: string | null;
  languageId: string;
  projectId: string;
  createdAt: string;
  updatedAt: string;
}

export interface CreateFrameworkInput {
  name: string;
  version: string;
  detectedAt: string;
  languageId: string;
  projectId: string;
}
```

---

## Task 2 — Services

**Files**
- CREATE `frontend/src/catalog/services/language.service.ts`
- CREATE `frontend/src/catalog/services/framework.service.ts`

### Steps

- [ ] Create `language.service.ts`

```ts
import type { ApiResponse } from '@/shared/types';
import type { PaginatedData } from '@/shared/types/crud';
import type { CreateLanguageInput, Language } from '@/catalog/types/language';
import { createCrudService } from '@/shared/services/createCrudService';
import { api } from '@/shared/utils/api';

const BASE_URL = '/catalog/languages';
const crud = createCrudService<Language, CreateLanguageInput, never>(BASE_URL);

export const languageService = {
  ...crud,

  list(page = 1, perPage = 20, projectId?: string): Promise<ApiResponse<PaginatedData<Language>>> {
    let url = `${BASE_URL}?page=${page}&per_page=${perPage}`;
    if (projectId) url += `&project_id=${projectId}`;
    return api.get<ApiResponse<PaginatedData<Language>>>(url);
  },
};
```

- [ ] Create `framework.service.ts`

```ts
import type { ApiResponse } from '@/shared/types';
import type { PaginatedData } from '@/shared/types/crud';
import type { CreateFrameworkInput, Framework } from '@/catalog/types/framework';
import { createCrudService } from '@/shared/services/createCrudService';
import { api } from '@/shared/utils/api';

const BASE_URL = '/catalog/frameworks';
const crud = createCrudService<Framework, CreateFrameworkInput, never>(BASE_URL);

export const frameworkService = {
  ...crud,

  list(page = 1, perPage = 20, projectId?: string): Promise<ApiResponse<PaginatedData<Framework>>> {
    let url = `${BASE_URL}?page=${page}&per_page=${perPage}`;
    if (projectId) url += `&project_id=${projectId}`;
    return api.get<ApiResponse<PaginatedData<Framework>>>(url);
  },
};
```

---

## Task 3 — Stores

**Files**
- CREATE `frontend/src/catalog/stores/language.ts`
- CREATE `frontend/src/catalog/stores/framework.ts`

### Steps

- [ ] Create `language.ts` store

```ts
import { ref } from 'vue';
import { defineStore } from 'pinia';
import type { CreateLanguageInput, Language } from '@/catalog/types/language';
import { languageService } from '@/catalog/services/language.service';
import { i18n } from '@/shared/i18n';

export const useLanguageStore = defineStore('catalog-language', () => {
  const t = i18n.global.t;
  const languages = ref<Language[]>([]);
  const selected = ref<Language | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const totalPages = ref(0);
  const currentPage = ref(1);
  const total = ref(0);

  async function fetchAll(page = 1, perPage = 20, projectId?: string): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      const response = await languageService.list(page, perPage, projectId);
      languages.value = response.data.items;
      totalPages.value = response.data.total_pages;
      currentPage.value = response.data.page;
      total.value = response.data.total;
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.languages') });
    } finally {
      loading.value = false;
    }
  }

  async function remove(id: string): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      await languageService.remove(id);
      languages.value = languages.value.filter((l) => l.id !== id);
    } catch {
      error.value = t('common.errors.failedToDelete', { entity: t('common.entities.languages') });
    } finally {
      loading.value = false;
    }
  }

  return { languages, selected, loading, error, totalPages, currentPage, total, fetchAll, remove };
});
```

- [ ] Create `framework.ts` store

```ts
import { ref } from 'vue';
import { defineStore } from 'pinia';
import type { CreateFrameworkInput, Framework } from '@/catalog/types/framework';
import { frameworkService } from '@/catalog/services/framework.service';
import { i18n } from '@/shared/i18n';

export const useFrameworkStore = defineStore('catalog-framework', () => {
  const t = i18n.global.t;
  const frameworks = ref<Framework[]>([]);
  const selected = ref<Framework | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const totalPages = ref(0);
  const currentPage = ref(1);
  const total = ref(0);

  async function fetchAll(page = 1, perPage = 20, projectId?: string): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      const response = await frameworkService.list(page, perPage, projectId);
      frameworks.value = response.data.items;
      totalPages.value = response.data.total_pages;
      currentPage.value = response.data.page;
      total.value = response.data.total;
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.frameworks') });
    } finally {
      loading.value = false;
    }
  }

  async function remove(id: string): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      await frameworkService.remove(id);
      frameworks.value = frameworks.value.filter((f) => f.id !== id);
    } catch {
      error.value = t('common.errors.failedToDelete', { entity: t('common.entities.frameworks') });
    } finally {
      loading.value = false;
    }
  }

  return { frameworks, selected, loading, error, totalPages, currentPage, total, fetchAll, remove };
});
```

---

## Task 4 — LanguageList page

**Files**
- CREATE `frontend/src/catalog/pages/LanguageList.vue`
- CREATE `frontend/src/catalog/composables/useLanguageFiltering.ts`
- CREATE `frontend/tests/unit/catalog/pages/LanguageList.spec.ts`

### Steps

- [ ] Create `useLanguageFiltering.ts`

```ts
import type { Ref } from 'vue';
import { computed, ref } from 'vue';
import type { Language } from '@/catalog/types/language';

export type LanguageSortField = 'name' | 'project' | 'status' | 'version';

export interface LanguageGroupedRow {
  groupIndex: number;
  groupSize: number;
  isFirstInGroup: boolean;
  lang: Language;
  projectId: string;
  projectName: string;
}

export interface UseLanguageFilteringOptions {
  languages: Ref<Language[]>;
  projectMap: Ref<Map<string, { name: string }>>;
}

export function useLanguageFiltering({ languages, projectMap }: UseLanguageFilteringOptions) {
  const search = ref('');
  const filterStatus = ref('');
  const filterLanguage = ref('');
  const sortField = ref<LanguageSortField>('project');
  const sortDir = ref<'asc' | 'desc'>('asc');

  function sortIndicator(field: LanguageSortField): string {
    if (sortField.value !== field) return '';
    return sortDir.value === 'asc' ? ' ↑' : ' ↓';
  }

  function toggleSort(field: LanguageSortField) {
    if (sortField.value === field) {
      sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
      sortField.value = field;
      sortDir.value = 'asc';
    }
  }

  const availableLanguages = computed(() => {
    const set = new Set<string>();
    for (const l of languages.value) set.add(l.name);
    return [...set].sort();
  });

  const filteredLanguages = computed(() => {
    return languages.value.filter((l) => {
      if (search.value) {
        const q = search.value.toLowerCase();
        const projName = projectMap.value.get(l.projectId)?.name ?? '';
        if (!projName.toLowerCase().includes(q) && !l.name.toLowerCase().includes(q)) return false;
      }
      if (filterStatus.value && l.maintenanceStatus !== filterStatus.value) return false;
      if (filterLanguage.value && l.name !== filterLanguage.value) return false;
      return true;
    });
  });

  const groupedLanguages = computed<LanguageGroupedRow[]>(() => {
    const groups = new Map<string, Language[]>();
    for (const l of filteredLanguages.value) {
      if (!groups.has(l.projectId)) groups.set(l.projectId, []);
      groups.get(l.projectId)!.push(l);
    }

    const dir = sortDir.value === 'asc' ? 1 : -1;
    const sorted = [...groups.entries()].sort(([keyA, a], [keyB, b]) => {
      const nameA = projectMap.value.get(keyA)?.name ?? '';
      const nameB = projectMap.value.get(keyB)?.name ?? '';
      return nameA.localeCompare(nameB) * dir;
    });

    const result: LanguageGroupedRow[] = [];
    let groupIndex = 0;
    for (const [key, langs] of sorted) {
      const projName = projectMap.value.get(key)?.name ?? key;
      langs.forEach((lang, i) => {
        result.push({
          groupIndex,
          groupSize: langs.length,
          isFirstInGroup: i === 0,
          lang,
          projectId: key,
          projectName: projName,
        });
      });
      groupIndex++;
    }
    return result;
  });

  return {
    availableLanguages,
    filteredLanguages,
    filterLanguage,
    filterStatus,
    groupedLanguages,
    search,
    sortDir,
    sortField,
    sortIndicator,
    toggleSort,
  };
}
```

- [ ] Create `LanguageList.vue`

```vue
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

const { availableLanguages, filterLanguage, filterStatus, groupedLanguages, search, sortIndicator, toggleSort } =
  useLanguageFiltering({ languages: computed(() => languageStore.languages), projectMap });

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
      <nav class="mb-6 flex items-center gap-1 text-sm text-text-muted" data-testid="language-list-breadcrumb">
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

      <div v-if="languageStore.loading" class="py-8 text-center text-text-muted" data-testid="language-list-loading">
        {{ t('common.actions.loading') }}
      </div>

      <div v-else-if="languageStore.error" class="rounded-lg bg-danger/10 p-4 text-danger" role="alert" data-testid="language-list-error">
        {{ languageStore.error }}
      </div>

      <template v-else>
        <div class="mb-4 flex flex-wrap items-center gap-3" data-testid="language-filters">
          <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <input v-model="search" type="search" :placeholder="t('catalog.languages.searchPlaceholder')" :aria-label="t('catalog.languages.searchPlaceholder')" class="w-full rounded-lg border border-border bg-surface py-2 pl-9 pr-3 text-sm text-text placeholder:text-text-muted focus:border-primary focus:outline-none" data-testid="language-search" />
          </div>
          <select v-model="filterLanguage" :aria-label="t('catalog.languages.allLanguages')" class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none" data-testid="language-filter-name">
            <option value="">{{ t('catalog.languages.allLanguages') }}</option>
            <option v-for="lang in availableLanguages" :key="lang" :value="lang">{{ lang }}</option>
          </select>
          <select v-model="filterStatus" :aria-label="t('catalog.languages.allStatuses')" class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none" data-testid="language-filter-status">
            <option value="">{{ t('catalog.languages.allStatuses') }}</option>
            <option value="active">{{ t('catalog.techStacks.statusActive') }}</option>
            <option value="eol">{{ t('catalog.techStacks.statusUnmaintained') }}</option>
          </select>
        </div>

        <div v-if="languageStore.languages.length > 0" class="overflow-hidden rounded-xl border border-border bg-surface">
          <table class="w-full" data-testid="language-list-table">
            <thead>
              <tr class="border-b border-border bg-surface-muted">
                <th class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text" @click="toggleSort('project')">
                  {{ t('catalog.languages.project') }}{{ sortIndicator('project') }}
                </th>
                <th class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text" @click="toggleSort('name')">
                  {{ t('catalog.languages.language') }}{{ sortIndicator('name') }}
                </th>
                <th class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text" @click="toggleSort('version')">
                  {{ t('catalog.languages.version') }}{{ sortIndicator('version') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('catalog.languages.eolDate') }}
                </th>
                <th class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text" @click="toggleSort('status')">
                  {{ t('catalog.languages.status') }}{{ sortIndicator('status') }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in groupedLanguages"
                :key="row.lang.id"
                :class="[row.isFirstInGroup ? 'border-t border-border first:border-0' : '', row.groupIndex % 2 === 1 ? 'bg-surface-muted/50' : '']"
                data-testid="language-list-row"
              >
                <td v-if="row.isFirstInGroup" :rowspan="row.groupSize" class="px-4 py-3 text-sm align-top">
                  <RouterLink :to="{ name: 'catalog-projects-detail', params: { id: row.projectId } }" class="font-medium text-primary hover:text-primary-dark">
                    {{ row.projectName }}
                  </RouterLink>
                </td>
                <td class="px-4 py-3 text-sm text-text">
                  <TechBadge :name="row.lang.name" :version="row.lang.version" size="sm" />
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">{{ row.lang.version || '—' }}</td>
                <td class="px-4 py-3 text-sm text-text-muted">{{ row.lang.eolDate ?? '—' }}</td>
                <td class="px-4 py-3 text-sm">
                  <span v-if="row.lang.maintenanceStatus === 'eol'" class="rounded-full bg-danger/10 px-2 py-0.5 text-xs font-medium text-danger" data-testid="language-eol-badge">
                    {{ t('catalog.techStacks.unmaintained') }}
                  </span>
                  <span v-else-if="row.lang.maintenanceStatus === 'active'" class="rounded-full bg-success/10 px-2 py-0.5 text-xs font-medium text-success" data-testid="language-active-badge">
                    {{ t('catalog.techStacks.statusActive') }}
                  </span>
                  <span v-else class="text-text-muted">—</span>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="groupedLanguages.length === 0" class="py-8 text-center text-text-muted" data-testid="language-list-no-match">
            {{ t('catalog.languages.noMatchingLanguages') }}
          </div>
        </div>

        <div v-else class="overflow-hidden rounded-xl border border-border bg-surface">
          <div class="flex flex-col items-center py-12" data-testid="language-list-empty">
            <svg class="mb-4 h-12 w-12 text-text-muted/50" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0L12 16.5l-5.571-2.25m11.142 0L21.75 16.5 12 21.75 2.25 16.5l4.179-2.25" />
            </svg>
            <p class="mb-1 text-sm font-medium text-text">{{ t('catalog.languages.noLanguages') }}</p>
            <p class="mb-4 text-sm text-text-muted">{{ t('catalog.languages.noLanguagesHint') }}</p>
            <RouterLink :to="{ name: 'catalog-providers-list' }" class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark" data-testid="language-empty-providers-link">
              {{ t('catalog.providers.title') }}
            </RouterLink>
          </div>
        </div>

        <Pagination v-if="languageStore.totalPages > 1" :page="languageStore.currentPage" :total-pages="languageStore.totalPages" data-testid="language-list-pagination" @update:page="changePage" />
      </template>
    </div>
  </DashboardLayout>
</template>
```

- [ ] Create `LanguageList.spec.ts`

```ts
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import LanguageList from '@/catalog/pages/LanguageList.vue';
import { useLanguageStore } from '@/catalog/stores/language';
import { useProjectStore } from '@/catalog/stores/project';

vi.mock('@/shared/i18n', () => ({
  i18n: { global: { t: (k: string) => k } },
}));

const router = createRouter({ history: createWebHistory(), routes: [{ path: '/', component: LanguageList }] });

describe('LanguageList', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('shows loading state', async () => {
    const store = useLanguageStore();
    store.loading = true;
    const wrapper = mount(LanguageList, { global: { plugins: [router, createPinia()] } });
    expect(wrapper.find('[data-testid="language-list-loading"]').exists()).toBe(true);
  });

  it('shows empty state when no languages', async () => {
    const langStore = useLanguageStore();
    langStore.loading = false;
    langStore.languages = [];
    const wrapper = mount(LanguageList, { global: { plugins: [router, createPinia()] } });
    expect(wrapper.find('[data-testid="language-list-empty"]').exists()).toBe(true);
  });

  it('renders rows for each language', async () => {
    const langStore = useLanguageStore();
    langStore.loading = false;
    langStore.languages = [
      { id: '1', name: 'PHP', version: '8.3', detectedAt: '2026-01-01', eolDate: null, maintenanceStatus: 'active', projectId: 'p1', createdAt: '2026-01-01', updatedAt: '2026-01-01' },
    ];
    const projStore = useProjectStore();
    projStore.projects = [{ id: 'p1', name: 'My Project' } as any];
    const wrapper = mount(LanguageList, { global: { plugins: [router, createPinia()] } });
    expect(wrapper.findAll('[data-testid="language-list-row"]')).toHaveLength(1);
  });
});
```

---

## Task 5 — FrameworkList page

**Files**
- CREATE `frontend/src/catalog/pages/FrameworkList.vue`
- CREATE `frontend/src/catalog/composables/useFrameworkGrouping.ts`
- CREATE `frontend/tests/unit/catalog/pages/FrameworkList.spec.ts`

### Steps

- [ ] Create `useFrameworkGrouping.ts` (adapted from `useTechStackGrouping`, frameworks-only)

```ts
import type { Ref } from 'vue';
import { computed, ref } from 'vue';
import type { Framework } from '@/catalog/types/framework';

export type FrameworkSortField = 'framework' | 'ltsGap' | 'project' | 'version';
export type FrameworkGroupBy = 'framework' | 'project' | 'provider';

export interface FrameworkGroupedRow {
  fw: Framework;
  groupIndex: number;
  groupSize: number;
  isFirstInGroup: boolean;
  projectId: string;
  projectName: string;
}

export interface ProviderAggregate {
  frameworks: { max: string; min: string; name: string }[];
  id: string;
  name: string;
  projectCount: number;
  type: string;
}

export interface UseFrameworkGroupingOptions {
  frameworks: Ref<Framework[]>;
  projectMap: Ref<Map<string, { name: string; providerId: null | string }>>;
  providerMap: Ref<Map<string, { name: string; type: string }>>;
}

export function useFrameworkGrouping({ frameworks, projectMap, providerMap }: UseFrameworkGroupingOptions) {
  const search = ref('');
  const filterFramework = ref('');
  const filterStatus = ref('');
  const filterProvider = ref('');
  const groupBy = ref<FrameworkGroupBy>('project');
  const sortField = ref<FrameworkSortField>('project');
  const sortDir = ref<'asc' | 'desc'>('asc');

  function sortIndicator(field: FrameworkSortField): string {
    if (sortField.value !== field) return '';
    return sortDir.value === 'asc' ? ' ↑' : ' ↓';
  }

  function toggleSort(field: FrameworkSortField) {
    if (sortField.value === field) {
      sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
      sortField.value = field;
      sortDir.value = 'asc';
    }
  }

  const availableFrameworks = computed(() => {
    const set = new Set<string>();
    for (const fw of frameworks.value) {
      if (fw.name && fw.name !== 'none') set.add(fw.name);
    }
    return [...set].sort();
  });

  const availableProviders = computed(() => {
    const result: { id: string; name: string }[] = [];
    for (const [id, info] of providerMap.value.entries()) result.push({ id, name: info.name });
    return result;
  });

  const filteredFrameworks = computed(() => {
    return frameworks.value.filter((fw) => {
      if (search.value) {
        const q = search.value.toLowerCase();
        const projName = projectMap.value.get(fw.projectId)?.name ?? '';
        if (!projName.toLowerCase().includes(q) && !fw.name.toLowerCase().includes(q)) return false;
      }
      if (filterFramework.value && fw.name !== filterFramework.value) return false;
      if (filterStatus.value && fw.maintenanceStatus !== filterStatus.value) return false;
      if (filterProvider.value) {
        const proj = projectMap.value.get(fw.projectId);
        if (proj?.providerId !== filterProvider.value) return false;
      }
      return true;
    });
  });

  const healthScore = computed(() => {
    const fws = filteredFrameworks.value;
    if (fws.length === 0) return null;
    let active = 0, eol = 0, warning = 0;
    for (const fw of fws) {
      if (fw.maintenanceStatus === 'eol') eol++;
      else if (fw.maintenanceStatus === 'warning') warning++;
      else active++;
    }
    return { active, eol, percent: Math.round((active / fws.length) * 100), total: fws.length, warning };
  });

  const providerAggregates = computed<ProviderAggregate[]>(() => {
    const agg = new Map<string, { frameworks: Map<string, string[]>; name: string; projectIds: Set<string>; type: string }>();
    for (const fw of frameworks.value) {
      if (!fw.name || fw.name === 'none') continue;
      const proj = projectMap.value.get(fw.projectId);
      if (!proj?.providerId) continue;
      const provider = providerMap.value.get(proj.providerId);
      if (!provider) continue;
      if (!agg.has(proj.providerId)) {
        agg.set(proj.providerId, { frameworks: new Map(), name: provider.name, projectIds: new Set(), type: provider.type });
      }
      const entry = agg.get(proj.providerId)!;
      entry.projectIds.add(fw.projectId);
      if (!entry.frameworks.has(fw.name)) entry.frameworks.set(fw.name, []);
      if (fw.version) entry.frameworks.get(fw.name)!.push(fw.version);
    }
    return [...agg.entries()].map(([id, entry]) => ({
      frameworks: [...entry.frameworks.entries()].map(([name, versions]) => {
        const sorted = [...versions].sort((a, b) => a.localeCompare(b, undefined, { numeric: true }));
        return { max: sorted[sorted.length - 1] ?? '—', min: sorted[0] ?? '—', name };
      }),
      id,
      name: entry.name,
      projectCount: entry.projectIds.size,
      type: entry.type,
    }));
  });

  function groupKey(fw: Framework): string {
    if (groupBy.value === 'framework') return fw.name;
    if (groupBy.value === 'provider') {
      return projectMap.value.get(fw.projectId)?.providerId ?? 'unknown';
    }
    return fw.projectId;
  }

  function groupLabel(key: string): string {
    if (groupBy.value === 'framework') return key;
    if (groupBy.value === 'provider') return providerMap.value.get(key)?.name ?? key;
    return projectMap.value.get(key)?.name ?? key;
  }

  function worstGap(fws: Framework[]): number {
    let worst = -1;
    for (const fw of fws) {
      if (!fw.ltsGap) continue;
      const rank = fw.maintenanceStatus === 'eol' ? 2 : fw.maintenanceStatus === 'warning' ? 1 : 0;
      if (rank > worst) worst = rank;
    }
    return worst;
  }

  const groupedFrameworks = computed<FrameworkGroupedRow[]>(() => {
    const groups = new Map<string, Framework[]>();
    for (const fw of filteredFrameworks.value) {
      const key = groupKey(fw);
      if (!groups.has(key)) groups.set(key, []);
      groups.get(key)!.push(fw);
    }

    const dir = sortDir.value === 'asc' ? 1 : -1;
    const sorted = [...groups.entries()].sort(([keyA, fwsA], [keyB, fwsB]) => {
      if (sortField.value === 'ltsGap') {
        const gA = worstGap(fwsA), gB = worstGap(fwsB);
        if (gA === -1 && gB === -1) return 0;
        if (gA === -1) return 1;
        if (gB === -1) return -1;
        return (gB - gA) * dir;
      }
      const labelA = groupLabel(keyA).toLowerCase();
      const labelB = groupLabel(keyB).toLowerCase();
      return labelA.localeCompare(labelB, undefined, { numeric: true }) * dir;
    });

    const result: FrameworkGroupedRow[] = [];
    let groupIndex = 0;
    for (const [key, fws] of sorted) {
      const label = groupLabel(key);
      fws.forEach((fw, i) => {
        result.push({ fw, groupIndex, groupSize: fws.length, isFirstInGroup: i === 0, projectId: fw.projectId, projectName: label });
      });
      groupIndex++;
    }
    return result;
  });

  return {
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
    sortDir,
    sortField,
    sortIndicator,
    toggleSort,
  };
}
```

- [ ] Create `FrameworkList.vue`

```vue
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
          <button v-for="mode in (['project', 'framework', 'provider'] as const)" :key="mode" :class="groupBy === mode ? 'border-primary bg-primary/10 text-primary' : 'border-border text-text-muted hover:border-primary/50'" class="rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors" @click="groupBy = mode">
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
```

- [ ] Create `FrameworkList.spec.ts`

```ts
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import FrameworkList from '@/catalog/pages/FrameworkList.vue';
import { useFrameworkStore } from '@/catalog/stores/framework';
import { useProjectStore } from '@/catalog/stores/project';

vi.mock('@/shared/i18n', () => ({ i18n: { global: { t: (k: string) => k } } }));

const router = createRouter({ history: createWebHistory(), routes: [{ path: '/', component: FrameworkList }] });

describe('FrameworkList', () => {
  beforeEach(() => setActivePinia(createPinia()));

  it('shows loading state', () => {
    const store = useFrameworkStore();
    store.loading = true;
    const wrapper = mount(FrameworkList, { global: { plugins: [router, createPinia()] } });
    expect(wrapper.find('[data-testid="framework-list-loading"]').exists()).toBe(true);
  });

  it('shows empty state when no frameworks', () => {
    const store = useFrameworkStore();
    store.loading = false;
    store.frameworks = [];
    const wrapper = mount(FrameworkList, { global: { plugins: [router, createPinia()] } });
    expect(wrapper.find('[data-testid="framework-list-empty"]').exists()).toBe(true);
  });

  it('renders rows for each framework', () => {
    const fwStore = useFrameworkStore();
    fwStore.loading = false;
    fwStore.frameworks = [
      { id: '1', name: 'Symfony', version: '7.2', detectedAt: '2026-01-01', latestLts: '7.2', ltsGap: null, maintenanceStatus: 'active', eolDate: null, versionSyncedAt: null, languageId: 'l1', projectId: 'p1', createdAt: '2026-01-01', updatedAt: '2026-01-01' },
    ];
    const projStore = useProjectStore();
    projStore.projects = [{ id: 'p1', name: 'My Project' } as any];
    const wrapper = mount(FrameworkList, { global: { plugins: [router, createPinia()] } });
    expect(wrapper.findAll('[data-testid="framework-list-row"]')).toHaveLength(1);
  });
});
```

---

## Task 6 — Routes + Navigation

**Files**
- MODIFY `frontend/src/catalog/routes.ts`
- MODIFY `frontend/src/shared/components/AppSidebar.vue`

### Steps

- [ ] In `routes.ts`, replace TechStack routes with Language + Framework routes

Replace:
```ts
{
  component: () => import('@/catalog/pages/TechStackList.vue'),
  meta: { layout: Layout.Dashboard },
  name: 'catalog-tech-stacks-list',
  path: '/catalog/tech-stacks',
},
{
  component: () => import('@/catalog/pages/TechStackForm.vue'),
  meta: { layout: Layout.Dashboard },
  name: 'catalog-tech-stacks-create',
  path: '/catalog/tech-stacks/new',
},
```

With:
```ts
{
  component: () => import('@/catalog/pages/LanguageList.vue'),
  meta: { layout: Layout.Dashboard },
  name: 'catalog-languages-list',
  path: '/catalog/languages',
},
{
  component: () => import('@/catalog/pages/FrameworkList.vue'),
  meta: { layout: Layout.Dashboard },
  name: 'catalog-frameworks-list',
  path: '/catalog/frameworks',
},
```

- [ ] In `AppSidebar.vue`, update the governance section

Replace:
```ts
{ icon: '🔧', labelKey: 'nav.techStacks', to: '/catalog/tech-stacks' },
```

With:
```ts
{ icon: '🗣', labelKey: 'nav.languages', to: '/catalog/languages' },
{ icon: '🔧', labelKey: 'nav.frameworks', to: '/catalog/frameworks' },
```

---

## Task 7 — ProjectDetail tab

**Files**
- CREATE `frontend/src/catalog/components/ProjectLanguagesTab.vue`
- CREATE `frontend/src/catalog/components/ProjectFrameworksTab.vue`
- MODIFY `frontend/src/catalog/pages/ProjectDetail.vue` (replace `ProjectTechStacksTab` usage)
- DELETE `frontend/src/catalog/components/ProjectTechStacksTab.vue`

### Steps

- [ ] Create `ProjectLanguagesTab.vue`

```vue
<script setup lang="ts">
import { onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

import { useLanguageStore } from '@/catalog/stores/language';
import Pagination from '@/shared/components/Pagination.vue';

const PER_PAGE = 20;
const props = defineProps<{ projectId: string }>();
const { t } = useI18n();
const languageStore = useLanguageStore();

onMounted(async () => {
  await languageStore.fetchAll(1, PER_PAGE, props.projectId);
});

function changePage(page: number) {
  languageStore.fetchAll(page, PER_PAGE, props.projectId);
}
</script>

<template>
  <div data-testid="languages-panel">
    <div class="overflow-hidden rounded-xl border border-border bg-surface">
      <table class="w-full">
        <thead>
          <tr class="border-b border-border bg-surface-muted">
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">{{ t('catalog.languages.language') }}</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">{{ t('catalog.languages.version') }}</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">{{ t('catalog.languages.eolDate') }}</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">{{ t('catalog.languages.status') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="lang in languageStore.languages" :key="lang.id" class="border-b border-border last:border-0" data-testid="language-row">
            <td class="px-4 py-3 text-sm text-text">{{ lang.name }}</td>
            <td class="px-4 py-3 text-sm text-text-muted">{{ lang.version || '—' }}</td>
            <td class="px-4 py-3 text-sm text-text-muted">{{ lang.eolDate ?? '—' }}</td>
            <td class="px-4 py-3 text-sm">
              <span v-if="lang.maintenanceStatus === 'eol'" class="rounded-full bg-danger/10 px-2 py-0.5 text-xs font-medium text-danger">{{ t('catalog.techStacks.unmaintained') }}</span>
              <span v-else-if="lang.maintenanceStatus === 'active'" class="rounded-full bg-success/10 px-2 py-0.5 text-xs font-medium text-success">{{ t('catalog.techStacks.statusActive') }}</span>
              <span v-else class="text-text-muted">—</span>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="languageStore.languages.length === 0" class="py-8 text-center text-text-muted" data-testid="languages-empty">
        {{ t('catalog.languages.noLanguages') }}
      </div>
    </div>
    <Pagination v-if="languageStore.totalPages > 1" :page="languageStore.currentPage" :total-pages="languageStore.totalPages" data-testid="languages-pagination" @update:page="changePage" />
  </div>
</template>
```

- [ ] Create `ProjectFrameworksTab.vue`

```vue
<script setup lang="ts">
import { onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

import { useFrameworkStore } from '@/catalog/stores/framework';
import Pagination from '@/shared/components/Pagination.vue';

const PER_PAGE = 20;
const props = defineProps<{ projectId: string }>();
const { t } = useI18n();
const frameworkStore = useFrameworkStore();

onMounted(async () => {
  await frameworkStore.fetchAll(1, PER_PAGE, props.projectId);
});

function changePage(page: number) {
  frameworkStore.fetchAll(page, PER_PAGE, props.projectId);
}
</script>

<template>
  <div data-testid="frameworks-panel">
    <div class="overflow-hidden rounded-xl border border-border bg-surface">
      <table class="w-full">
        <thead>
          <tr class="border-b border-border bg-surface-muted">
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">{{ t('catalog.frameworks.framework') }}</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">{{ t('catalog.frameworks.version') }}</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">{{ t('catalog.techStacks.latestLts') }}</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">{{ t('catalog.techStacks.ltsGap') }}</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">{{ t('catalog.techStacks.syncedAt') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="fw in frameworkStore.frameworks" :key="fw.id" class="border-b border-border last:border-0" data-testid="framework-row">
            <td class="px-4 py-3 text-sm text-text">{{ fw.name }}</td>
            <td class="px-4 py-3 text-sm text-text-muted">
              <span class="inline-flex items-center gap-1.5">
                {{ fw.version || '—' }}
                <span v-if="fw.maintenanceStatus === 'eol'" class="rounded-full bg-danger/10 px-1.5 py-0.5 text-xs font-medium text-danger" :title="fw.eolDate ? t('catalog.techStacks.unmaintainedSince', { date: fw.eolDate }) : t('catalog.techStacks.unmaintainedNoDate')">{{ t('catalog.techStacks.unmaintained') }}</span>
                <span v-else-if="fw.maintenanceStatus === 'warning'" class="rounded-full bg-warning/10 px-1.5 py-0.5 text-xs font-medium text-warning">{{ t('catalog.techStacks.inactive') }}</span>
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-text-muted">{{ fw.latestLts ?? '—' }}</td>
            <td class="px-4 py-3 text-sm">
              <span v-if="fw.ltsGap" :class="{ 'text-success': fw.maintenanceStatus === 'active', 'text-warning': fw.maintenanceStatus === 'warning', 'text-danger': fw.maintenanceStatus === 'eol' }">{{ fw.ltsGap }}</span>
              <span v-else class="text-text-muted">—</span>
            </td>
            <td class="px-4 py-3 text-sm text-text-muted">{{ fw.versionSyncedAt ?? '—' }}</td>
          </tr>
        </tbody>
      </table>
      <div v-if="frameworkStore.frameworks.length === 0" class="py-8 text-center text-text-muted" data-testid="frameworks-empty">
        {{ t('catalog.frameworks.noFrameworks') }}
      </div>
    </div>
    <Pagination v-if="frameworkStore.totalPages > 1" :page="frameworkStore.currentPage" :total-pages="frameworkStore.totalPages" data-testid="frameworks-pagination" @update:page="changePage" />
  </div>
</template>
```

- [ ] In `ProjectDetail.vue`, find the tab definition for `techStacks` and replace it with two tabs `languages` + `frameworks`. Replace the import of `ProjectTechStacksTab` with `ProjectLanguagesTab` + `ProjectFrameworksTab`. Replace the tab panel rendering accordingly.

  Search for the tab label key `catalog.projects.techStacks` and replace with two entries:
  - `{ key: 'languages', label: 'catalog.projects.languages' }`
  - `{ key: 'frameworks', label: 'catalog.projects.frameworks' }`

  Replace `<ProjectTechStacksTab>` usage with the two new components (one per active tab).

---

## Task 8 — i18n

**Files**
- MODIFY `frontend/src/shared/i18n/locales/fr.json`
- MODIFY `frontend/src/shared/i18n/locales/en.json`

### Steps

- [ ] Add to `fr.json` under `common.entities`:

```json
"languages": "les langages",
"frameworks": "les frameworks"
```

- [ ] Add to `fr.json` under `nav` (alongside existing keys):

```json
"languages": "Langages",
"frameworks": "Frameworks"
```

- [ ] Add to `fr.json` under `catalog.projects` (alongside `techStacks`):

```json
"languages": "Langages",
"frameworks": "Frameworks"
```

- [ ] Add to `fr.json` under `catalog`:

```json
"languages": {
  "title": "Langages",
  "language": "Langage",
  "version": "Version",
  "eolDate": "Fin de support",
  "status": "Statut",
  "project": "Projet",
  "searchPlaceholder": "Rechercher un projet ou langage…",
  "allLanguages": "Tous les langages",
  "allStatuses": "Tous les statuts",
  "noLanguages": "Aucun langage détecté",
  "noLanguagesHint": "Importez des projets depuis un fournisseur et lancez un scan pour détecter les langages.",
  "noMatchingLanguages": "Aucun langage ne correspond aux filtres."
},
"frameworks": {
  "title": "Frameworks",
  "framework": "Framework",
  "language": "Langage",
  "version": "Version",
  "project": "Projet",
  "searchPlaceholder": "Rechercher un projet ou framework…",
  "allFrameworks": "Tous les frameworks",
  "noFrameworks": "Aucun framework détecté",
  "noFrameworksHint": "Importez des projets depuis un fournisseur et lancez un scan pour détecter les frameworks.",
  "noMatchingFrameworks": "Aucun framework ne correspond aux filtres."
}
```

- [ ] Mirror all keys in `en.json` with English values

---

## Task 9 — Cleanup

**Files to delete**
- `frontend/src/catalog/pages/TechStackList.vue`
- `frontend/src/catalog/pages/TechStackForm.vue`
- `frontend/src/catalog/components/TechStackTable.vue`
- `frontend/src/catalog/components/TechStackFilters.vue`
- `frontend/src/catalog/composables/useTechStackGrouping.ts`
- `frontend/src/catalog/types/tech-stack.ts`
- `frontend/src/catalog/services/tech-stack.service.ts`
- `frontend/src/catalog/stores/tech-stack.ts`
- `frontend/src/catalog/components/ProjectTechStacksTab.vue`
- Any existing TechStack-related test files under `frontend/tests/`

### Steps

- [ ] Delete files listed above:

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend sh -c "
  rm -f src/catalog/pages/TechStackList.vue \
        src/catalog/pages/TechStackForm.vue \
        src/catalog/components/TechStackTable.vue \
        src/catalog/components/TechStackFilters.vue \
        src/catalog/composables/useTechStackGrouping.ts \
        src/catalog/types/tech-stack.ts \
        src/catalog/services/tech-stack.service.ts \
        src/catalog/stores/tech-stack.ts \
        src/catalog/components/ProjectTechStacksTab.vue
"
```

- [ ] Search for any remaining TechStack imports across the codebase and fix them:

```bash
grep -r "TechStack\|tech-stack\|techStack" frontend/src --include="*.ts" --include="*.vue" -l
```

---

## Task 10 — Final verification

### Steps

- [ ] TypeScript check

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm vue-tsc --noEmit
```

- [ ] Lint

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm lint
```

- [ ] Tests

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm test --run
```

- [ ] Format check

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm format:check
```

- [ ] Confirm navigation: `/catalog/languages` and `/catalog/frameworks` are accessible, sidebar shows 2 entries under Gouvernance, project detail shows Languages + Frameworks tabs.
