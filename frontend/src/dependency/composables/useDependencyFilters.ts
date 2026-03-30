import { computed, ref } from 'vue';
import type { Ref } from 'vue';
import { useListFiltering } from '@/shared/composables/useListFiltering';
import type { Dependency, DependencyFilters } from '@/dependency/types';

export function useDependencyFilters(
  deps: Ref<Dependency[]>,
  projectMap: Ref<Map<string, string>>,
) {
  const filters = ref<DependencyFilters>({
    search: '',
    packageManager: '',
    type: '',
    status: '',
    projectId: '',
  });

  const { sortField, sortDir, sortIndicator, toggleSort } = useListFiltering(deps, {
    defaultSortField: 'project',
    searchFields: ['name'],
  });

  const filteredDeps = computed(() =>
    deps.value.filter((dep) => {
      const { search, packageManager, type, status, projectId } = filters.value;
      if (search) {
        const q = search.toLowerCase();
        const proj = projectMap.value.get(dep.projectId) ?? '';
        if (!dep.name.toLowerCase().includes(q) && !proj.toLowerCase().includes(q)) return false;
      }
      if (projectId && dep.projectId !== projectId) return false;
      if (packageManager && dep.packageManager !== packageManager) return false;
      if (type && dep.type !== type) return false;
      if (status === 'outdated' && !dep.isOutdated) return false;
      if (status === 'uptodate' && dep.isOutdated) return false;
      return true;
    }),
  );

  return { filters, filteredDeps, sortField, sortDir, sortIndicator, toggleSort };
}
