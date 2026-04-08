import type { ComputedRef, Ref } from 'vue';
import { humanizeMs, humanizeTimeDiff } from '@/catalog/composables/useFrameworkLts';
import { exportDependenciesPdf } from '@/dependency/services/dependencyPdfExport';
import type { DepGapStats, Dependency, HealthScore } from '@/dependency/types';

interface ExportStrategy {
  execute(deps: Dependency[], projectName: (id: string) => string, extra?: unknown): void;
}

const csvStrategy: ExportStrategy = {
  execute(deps, projectName) {
    const headers = [
      'Projet',
      'Nom',
      'Version',
      'Dernière version',
      'Package Manager',
      'Type',
      'Statut',
      'Vulnérabilités',
    ];
    const rows = deps.map((dep) => [
      projectName(dep.projectId),
      dep.name,
      dep.currentVersion,
      dep.latestVersion,
      dep.packageManager,
      dep.type,
      dep.isOutdated ? 'Obsolète' : 'À jour',
      String(dep.vulnerabilityCount),
    ]);
    const csv = [headers, ...rows].map((r) => r.map((c) => `"${c}"`).join(',')).join('\n');
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'dependencies.csv';
    a.click();
    URL.revokeObjectURL(url);
  },
};

const pdfStrategy: ExportStrategy = {
  execute(deps, projectName, extra) {
    const { healthScore, gapStats } = extra as {
      healthScore: HealthScore | null;
      gapStats: DepGapStats | null;
    };
    const rows = deps.map((dep) => ({
      project: projectName(dep.projectId),
      name: dep.name,
      currentVersion: dep.currentVersion,
      latestVersion: dep.latestVersion,
      packageManager: dep.packageManager,
      type: dep.type,
      status: dep.isOutdated ? 'Obsolete' : 'A jour',
      vulnerabilities: dep.vulnerabilityCount,
      gap:
        dep.isOutdated && dep.currentVersionReleasedAt && dep.latestVersionReleasedAt
          ? humanizeTimeDiff(dep.currentVersionReleasedAt, dep.latestVersionReleasedAt)
          : dep.isOutdated
            ? '-'
            : 'A jour',
    }));
    const gapData = gapStats
      ? {
          average: humanizeMs(gapStats.average),
          median: humanizeMs(gapStats.median),
          cumulated: humanizeMs(gapStats.cumulated),
        }
      : null;
    exportDependenciesPdf(rows, healthScore, gapData);
  },
};

const strategies: Record<string, ExportStrategy> = { csv: csvStrategy, pdf: pdfStrategy };

export function useDependencyExport(
  filteredDeps: ComputedRef<Dependency[]>,
  projectName: (id: string) => string,
  healthScore: Ref<HealthScore | null>,
  depGapStats: ComputedRef<DepGapStats | null>,
) {
  function handleExport(format: 'csv' | 'pdf') {
    const strategy = strategies[format];
    if (!strategy) return;
    strategy.execute(filteredDeps.value, projectName, {
      healthScore: healthScore.value,
      gapStats: depGapStats.value,
    });
  }

  return { handleExport };
}
