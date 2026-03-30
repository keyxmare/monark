import { computed } from 'vue';
import type { ComputedRef, Ref } from 'vue';
import type { Dependency, GroupedDepRow, SortField } from '@/dependency/types';

export function useDependencyGrouping(
  filteredDeps: ComputedRef<Dependency[]>,
  projectName: (id: string) => string,
  sortField: Ref<string>,
  sortDir: Ref<'asc' | 'desc'>,
) {
  const groupedDeps = computed<GroupedDepRow[]>(() => {
    const groups = new Map<string, Dependency[]>();
    for (const dep of filteredDeps.value) {
      if (!groups.has(dep.name)) groups.set(dep.name, []);
      groups.get(dep.name)!.push(dep);
    }

    const dir = sortDir.value === 'asc' ? 1 : -1;
    const sorted = [...groups.entries()].sort(([nameA, depsA], [nameB, depsB]) => {
      switch (sortField.value as SortField) {
        case 'name': return nameA.localeCompare(nameB) * dir;
        case 'project':
          return projectName(depsA[0]?.projectId ?? '').localeCompare(
            projectName(depsB[0]?.projectId ?? ''),
          ) * dir;
        case 'status': {
          const diff = depsA.filter(d => d.isOutdated).length - depsB.filter(d => d.isOutdated).length;
          return diff * dir;
        }
        case 'vulnerabilities': {
          const vA = depsA.reduce((s, d) => s + d.vulnerabilityCount, 0);
          const vB = depsB.reduce((s, d) => s + d.vulnerabilityCount, 0);
          return (vB - vA) * dir;
        }
        default: return 0;
      }
    });

    const rows: GroupedDepRow[] = [];
    let groupIndex = 0;
    for (const [, deps] of sorted) {
      deps.forEach((dep, i) => {
        rows.push({
          dep, groupIndex,
          groupSize: deps.length,
          isFirstInGroup: i === 0,
          projectId: dep.projectId,
          projectName: projectName(dep.projectId),
        });
      });
      groupIndex++;
    }
    return rows;
  });

  return { groupedDeps };
}
