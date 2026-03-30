import type { Ref } from 'vue';

import { computed, ref } from 'vue';

import type { TechStack } from '@/catalog/types/tech-stack';

export type ViewMode = 'frameworks' | 'languages';
export type GroupBy = 'framework' | 'language' | 'project' | 'provider';
export type SortField =
  | 'framework'
  | 'frameworkVersion'
  | 'language'
  | 'languageVersion'
  | 'ltsGap'
  | 'project';

export interface GroupedStack {
  groupIndex: number;
  groupSize: number;
  isFirstInGroup: boolean;
  projectId: string;
  projectName: string;
  ts: TechStack;
}

export interface ProviderAggregate {
  frameworks: { max: string; min: string; name: string }[];
  id: string;
  name: string;
  projectCount: number;
  type: string;
}

export interface ProjectInfo {
  name: string;
  providerId: null | string;
}

export interface ProviderInfo {
  name: string;
  type: string;
}

export interface UseTechStackGroupingOptions {
  projectMap: Ref<Map<string, ProjectInfo>>;
  providerMap: Ref<Map<string, ProviderInfo>>;
  techStacks: Ref<TechStack[]>;
}

export function useTechStackGrouping(options: UseTechStackGroupingOptions) {
  const { projectMap, providerMap, techStacks } = options;

  const viewMode = ref<ViewMode>('frameworks');
  const search = ref('');
  const filterFramework = ref('');
  const filterLanguage = ref('');
  const filterProvider = ref('');
  const filterStatus = ref('');
  const groupBy = ref<GroupBy>('project');
  const sortField = ref<SortField>('project');
  const sortDir = ref<'asc' | 'desc'>('asc');

  function sortIndicator(field: SortField): string {
    if (sortField.value !== field) return '';
    return sortDir.value === 'asc' ? ' ↑' : ' ↓';
  }

  function toggleSort(field: SortField) {
    if (sortField.value === field) {
      sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
      sortField.value = field;
      sortDir.value = 'asc';
    }
  }

  const availableFrameworks = computed(() => {
    const set = new Set<string>();
    for (const ts of techStacks.value) {
      if (ts.framework && ts.framework !== 'none') set.add(ts.framework);
    }
    return [...set].sort();
  });

  const availableLanguages = computed(() => {
    const set = new Set<string>();
    for (const ts of techStacks.value) {
      if (ts.language) set.add(ts.language);
    }
    return [...set].sort();
  });

  const availableProviders = computed(() => {
    const result: { id: string; name: string }[] = [];
    for (const [id, info] of providerMap.value.entries()) {
      result.push({ id, name: info.name });
    }
    return result;
  });

  const filteredStacks = computed(() => {
    return techStacks.value.filter((ts) => {
      if (search.value) {
        const q = search.value.toLowerCase();
        const projName = projectMap.value.get(ts.projectId)?.name ?? '';
        const target =
          viewMode.value === 'languages' ? ts.language.toLowerCase() : ts.framework.toLowerCase();
        if (!projName.toLowerCase().includes(q) && !target.includes(q)) return false;
      }
      if (filterFramework.value && ts.framework !== filterFramework.value) return false;
      if (filterLanguage.value && ts.language !== filterLanguage.value) return false;
      if (filterProvider.value) {
        const proj = projectMap.value.get(ts.projectId);
        if (proj?.providerId !== filterProvider.value) return false;
      }
      if (filterStatus.value) {
        const status = ts.maintenanceStatus;
        if (filterStatus.value !== status) return false;
      }
      return true;
    });
  });

  const providerAggregates = computed<ProviderAggregate[]>(() => {
    const agg = new Map<
      string,
      { frameworks: Map<string, string[]>; name: string; projectIds: Set<string>; type: string }
    >();

    for (const ts of techStacks.value) {
      if (ts.framework === 'none' || !ts.framework) continue;

      const proj = projectMap.value.get(ts.projectId);
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
      entry.projectIds.add(ts.projectId);

      if (!entry.frameworks.has(ts.framework)) {
        entry.frameworks.set(ts.framework, []);
      }
      if (ts.frameworkVersion) {
        entry.frameworks.get(ts.framework)!.push(ts.frameworkVersion);
      }
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

  const healthScore = computed(() => {
    const stacks = filteredStacks.value;
    if (stacks.length === 0) return null;

    let active = 0;
    let eol = 0;
    let warning = 0;

    for (const ts of stacks) {
      const status = ts.maintenanceStatus;
      if (status === 'eol') eol++;
      else if (status === 'warning') warning++;
      else active++;
    }

    return {
      active,
      eol,
      percent: Math.round((active / stacks.length) * 100),
      total: stacks.length,
      warning,
    };
  });

  function groupKey(ts: TechStack): string {
    if (groupBy.value === 'framework') return ts.framework;
    if (groupBy.value === 'language') return ts.language;
    if (groupBy.value === 'provider') {
      const proj = projectMap.value.get(ts.projectId);
      return proj?.providerId ?? 'unknown';
    }
    return ts.projectId;
  }

  function groupLabel(key: string): string {
    if (groupBy.value === 'framework' || groupBy.value === 'language') return key;
    if (groupBy.value === 'provider') {
      return providerMap.value.get(key)?.name ?? key;
    }
    return projectMap.value.get(key)?.name ?? key;
  }

  function worstGapForGroup(stacks: TechStack[]): number {
    let worst = -1;
    for (const ts of stacks) {
      if (!ts.ltsGap) continue;
      if (ts.maintenanceStatus === 'active') {
        if (worst < 0) worst = 0;
      } else {
        // Use a simple ranking: eol > warning > active, mapped to numeric
        const rank =
          ts.maintenanceStatus === 'eol' ? 2 : ts.maintenanceStatus === 'warning' ? 1 : 0;
        if (rank > worst) worst = rank;
      }
    }
    return worst;
  }

  function sortValueForGroup(stacks: TechStack[], key: string): number | string {
    switch (sortField.value) {
      case 'framework':
        return stacks[0]?.framework?.toLowerCase() ?? '';
      case 'frameworkVersion':
        return stacks[0]?.frameworkVersion ?? '';
      case 'language':
        return stacks[0]?.language?.toLowerCase() ?? '';
      case 'languageVersion':
        return stacks[0]?.version ?? '';
      case 'ltsGap':
        return worstGapForGroup(stacks);
      case 'project':
        return groupLabel(key).toLowerCase();
      default:
        return 0;
    }
  }

  const groupedStacks = computed<GroupedStack[]>(() => {
    const groups = new Map<string, TechStack[]>();
    for (const ts of filteredStacks.value) {
      const key = groupKey(ts);
      if (!groups.has(key)) {
        groups.set(key, []);
      }
      groups.get(key)!.push(ts);
    }

    const dir = sortDir.value === 'asc' ? 1 : -1;
    const sortedEntries = [...groups.entries()].sort(([keyA, stacksA], [keyB, stacksB]) => {
      const valA = sortValueForGroup(stacksA, keyA);
      const valB = sortValueForGroup(stacksB, keyB);

      if (sortField.value === 'ltsGap') {
        const gapA = valA as number;
        const gapB = valB as number;
        if (gapA === -1 && gapB === -1) return 0;
        if (gapA === -1) return 1;
        if (gapB === -1) return -1;
        return (gapB - gapA) * dir;
      }

      return String(valA).localeCompare(String(valB), undefined, { numeric: true }) * dir;
    });

    const result: GroupedStack[] = [];
    let groupIndex = 0;
    for (const [key, stacks] of sortedEntries) {
      const label = groupLabel(key);
      stacks.forEach((ts, i) => {
        result.push({
          groupIndex,
          groupSize: stacks.length,
          isFirstInGroup: i === 0,
          projectId: ts.projectId,
          projectName: label,
          ts,
        });
      });
      groupIndex++;
    }
    return result;
  });

  return {
    availableFrameworks,
    availableLanguages,
    availableProviders,
    filterFramework,
    filterLanguage,
    filterProvider,
    filteredStacks,
    filterStatus,
    groupBy,
    groupedStacks,
    healthScore,
    providerAggregates,
    search,
    sortDir,
    sortField,
    sortIndicator,
    toggleSort,
    viewMode,
  };
}
