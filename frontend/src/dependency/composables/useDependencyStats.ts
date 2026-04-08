import { computed, ref, watch } from 'vue';
import type { ComputedRef, Ref } from 'vue';
import { dependencyService } from '@/dependency/services/dependency.service';
import type { Dependency, DepGapStats, DependencyFilters, HealthScore } from '@/dependency/types';

export function useDependencyStats(
  allDeps: Ref<Dependency[]>,
  filteredDeps: ComputedRef<Dependency[]>,
  filters: Ref<DependencyFilters>,
  projectName: (id: string) => string,
) {
  const healthScore = ref<HealthScore | null>(null);

  const depGapStats = computed<DepGapStats | null>(() => {
    const maxGap = new Map<string, number>();
    for (const dep of filteredDeps.value) {
      if (!dep.isOutdated || !dep.currentVersionReleasedAt || !dep.latestVersionReleasedAt)
        continue;
      const gapMs = Math.abs(
        new Date(dep.latestVersionReleasedAt).getTime() -
          new Date(dep.currentVersionReleasedAt).getTime(),
      );
      if (gapMs > (maxGap.get(dep.name) ?? 0)) maxGap.set(dep.name, gapMs);
    }
    const gaps = [...maxGap.values()];
    if (gaps.length === 0) return null;
    const sorted = [...gaps].sort((a, b) => a - b);
    const cumulated = gaps.reduce((s, g) => s + g, 0);
    const median =
      sorted.length % 2 === 0
        ? (sorted[sorted.length / 2 - 1] + sorted[sorted.length / 2]) / 2
        : sorted[Math.floor(sorted.length / 2)];
    return { average: cumulated / gaps.length, cumulated, median };
  });

  const projectAggregates = computed(() => {
    const agg = new Map<string, { name: string; outdated: number; total: number; vulns: number }>();
    for (const dep of allDeps.value) {
      const name = projectName(dep.projectId);
      if (!agg.has(dep.projectId))
        agg.set(dep.projectId, { name, outdated: 0, total: 0, vulns: 0 });
      const e = agg.get(dep.projectId)!;
      e.total++;
      if (dep.isOutdated) e.outdated++;
      e.vulns += dep.vulnerabilityCount;
    }
    return [...agg.entries()].map(([id, v]) => ({ id, ...v }));
  });

  async function loadStats() {
    try {
      const params: Record<string, string> = {};
      if (filters.value.projectId) params.projectId = filters.value.projectId;
      if (filters.value.packageManager) params.packageManager = filters.value.packageManager;
      if (filters.value.type) params.type = filters.value.type;
      const { data: s } = await dependencyService.stats(params);
      healthScore.value = {
        outdated: s.outdated,
        percent: s.total > 0 ? Math.round((s.upToDate / s.total) * 100) : 100,
        total: s.total,
        totalVulns: s.totalVulnerabilities,
        upToDate: s.upToDate,
      };
    } catch {
      /* silently ignored */
    }
  }

  watch(
    [() => filters.value.projectId, () => filters.value.packageManager, () => filters.value.type],
    loadStats,
  );

  return { depGapStats, healthScore, loadStats, projectAggregates };
}
