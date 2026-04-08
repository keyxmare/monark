import { computed, ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useDependencyExport } from '../useDependencyExport';

vi.mock('@/dependency/services/dependencyPdfExport', () => ({
  exportDependenciesPdf: vi.fn(),
}));
vi.mock('@/catalog/composables/useFrameworkLts', () => ({
  humanizeMs: (n: number) => `${n}ms`,
  humanizeTimeDiff: () => '1 year',
}));

describe('useDependencyExport', () => {
  beforeEach(() => {
    globalThis.URL.createObjectURL = vi.fn(() => 'blob:mock');
    globalThis.URL.revokeObjectURL = vi.fn();
    document.createElement = vi.fn(() => ({ click: vi.fn(), href: '', download: '' }) as never);
  });

  it('calls pdf export with correct shape', async () => {
    const { exportDependenciesPdf } = await import('@/dependency/services/dependencyPdfExport');
    const deps = computed(
      () =>
        [
          {
            id: '1',
            name: 'lib',
            isOutdated: false,
            projectId: 'p1',
            vulnerabilityCount: 0,
            currentVersion: '1.0.0',
            latestVersion: '1.0.0',
            packageManager: 'npm',
            type: 'runtime',
            currentVersionReleasedAt: null,
            latestVersionReleasedAt: null,
          },
        ] as never,
    );
    const { handleExport } = useDependencyExport(
      deps,
      () => 'My Project',
      ref(null),
      computed(() => null),
    );
    handleExport('pdf');
    expect(exportDependenciesPdf).toHaveBeenCalled();
  });
});
