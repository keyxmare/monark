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

export function useFrameworkGrouping({
  frameworks,
  projectMap,
  providerMap,
}: UseFrameworkGroupingOptions) {
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
    let active = 0,
      eol = 0,
      warning = 0;
    for (const fw of fws) {
      if (fw.maintenanceStatus === 'eol') eol++;
      else if (fw.maintenanceStatus === 'warning') warning++;
      else active++;
    }
    return {
      active,
      eol,
      percent: Math.round((active / fws.length) * 100),
      total: fws.length,
      warning,
    };
  });

  const providerAggregates = computed<ProviderAggregate[]>(() => {
    const agg = new Map<
      string,
      { frameworks: Map<string, string[]>; name: string; projectIds: Set<string>; type: string }
    >();
    for (const fw of frameworks.value) {
      if (!fw.name || fw.name === 'none') continue;
      const proj = projectMap.value.get(fw.projectId);
      if (!proj?.providerId) continue;
      const provider = providerMap.value.get(proj.providerId);
      if (!provider) continue;
      if (!agg.has(proj.providerId)) {
        agg.set(proj.providerId, {
          frameworks: new Map(),
          name: provider.name,
          projectIds: new Set(),
          type: provider.type,
        });
      }
      const entry = agg.get(proj.providerId)!;
      entry.projectIds.add(fw.projectId);
      if (!entry.frameworks.has(fw.name)) entry.frameworks.set(fw.name, []);
      if (fw.version) entry.frameworks.get(fw.name)!.push(fw.version);
    }
    return [...agg.entries()].map(([id, entry]) => ({
      frameworks: [...entry.frameworks.entries()].map(([name, versions]) => {
        const sorted = [...versions].sort((a, b) =>
          a.localeCompare(b, undefined, { numeric: true }),
        );
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
        const gA = worstGap(fwsA),
          gB = worstGap(fwsB);
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
        result.push({
          fw,
          groupIndex,
          groupSize: fws.length,
          isFirstInGroup: i === 0,
          projectId: fw.projectId,
          projectName: label,
        });
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
