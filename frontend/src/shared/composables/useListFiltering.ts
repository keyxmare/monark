import type { Ref } from 'vue';

import { computed, ref, watch } from 'vue';

export interface UseListFilteringOptions {
  debounceMs?: number;
  defaultSortDir?: 'asc' | 'desc';
  defaultSortField: string;
  searchFields?: string[];
}

export function useListFiltering<T extends Record<string, unknown>>(
  items: Ref<T[]>,
  options: UseListFilteringOptions,
) {
  const { debounceMs = 300, defaultSortDir = 'asc', defaultSortField, searchFields = [] } = options;

  const sortField = ref(defaultSortField);
  const sortDir = ref<'asc' | 'desc'>(defaultSortDir);
  const search = ref('');
  const debouncedSearch = ref('');
  let debounceTimer: ReturnType<typeof setTimeout> | null = null;

  watch(search, (val) => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      debouncedSearch.value = val;
    }, debounceMs);
  });

  function toggleSort(field: string) {
    if (sortField.value === field) {
      sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
      sortField.value = field;
      sortDir.value = 'asc';
    }
  }

  function sortIndicator(field: string): string {
    if (sortField.value !== field) return '';
    return sortDir.value === 'asc' ? ' ↑' : ' ↓';
  }

  const filtered = computed(() => {
    if (!debouncedSearch.value || searchFields.length === 0) return items.value;
    const term = debouncedSearch.value.toLowerCase();
    return items.value.filter((item) =>
      searchFields.some((field) => {
        const val = item[field];
        return typeof val === 'string' && val.toLowerCase().includes(term);
      }),
    );
  });

  const sorted = computed(() => {
    const arr = [...filtered.value];
    const field = sortField.value;
    const dir = sortDir.value === 'asc' ? 1 : -1;
    return arr.sort((a, b) => {
      const aVal = a[field];
      const bVal = b[field];
      if (aVal === bVal) return 0;
      if (aVal === null || aVal === undefined) return 1;
      if (bVal === null || bVal === undefined) return -1;
      if (typeof aVal === 'string' && typeof bVal === 'string') {
        return aVal.localeCompare(bVal) * dir;
      }
      return ((aVal as number) - (bVal as number)) * dir;
    });
  });

  return {
    debouncedSearch,
    filtered,
    search,
    sortDir,
    sortField,
    sortIndicator,
    sorted,
    toggleSort,
  };
}
