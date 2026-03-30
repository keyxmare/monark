import { computed, ref } from 'vue';
import { describe, expect, it, vi } from 'vitest';
import { useDependencyStats } from '../useDependencyStats';

vi.mock('@/dependency/services/dependency.service', () => ({
  dependencyService: { stats: vi.fn() },
}));

describe('depGapStats', () => {
  it('returns null when no outdated deps', () => {
    const deps = ref([{ isOutdated: false, name: 'a', currentVersionReleasedAt: null, latestVersionReleasedAt: null }] as never);
    const { depGapStats } = useDependencyStats(deps, computed(() => deps.value), ref({ search: '', packageManager: '', type: '', status: '', projectId: '' }), (id) => id);
    expect(depGapStats.value).toBeNull();
  });

  it('computes average/median/cumulated', () => {
    const deps = ref([{
      isOutdated: true, name: 'lib',
      currentVersionReleasedAt: '2023-01-01T00:00:00Z',
      latestVersionReleasedAt: '2024-01-01T00:00:00Z',
    }] as never);
    const { depGapStats } = useDependencyStats(deps, computed(() => deps.value), ref({ search: '', packageManager: '', type: '', status: '', projectId: '' }), (id) => id);
    expect(depGapStats.value?.average).toBeGreaterThan(0);
  });
});
