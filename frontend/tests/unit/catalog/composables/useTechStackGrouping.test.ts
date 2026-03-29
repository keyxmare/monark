import { describe, expect, it } from 'vitest';
import { ref } from 'vue';

import { useTechStackGrouping } from '@/catalog/composables/useTechStackGrouping';
import type { TechStack } from '@/catalog/types/tech-stack';

function makeTechStack(overrides: Partial<TechStack> = {}): TechStack {
  return {
    id: 'ts-1',
    language: 'PHP',
    framework: 'Symfony',
    version: '8.4',
    frameworkVersion: '7.2',
    detectedAt: '2026-01-01',
    projectId: 'proj-1',
    createdAt: '2026-01-01',
    ...overrides,
  };
}

function createGrouping(stacks: TechStack[]) {
  const projectMap = ref(
    new Map([
      ['proj-1', { name: 'Project Alpha', providerId: 'prov-1' }],
      ['proj-2', { name: 'Project Beta', providerId: 'prov-1' }],
      ['proj-3', { name: 'Project Gamma', providerId: 'prov-2' }],
    ]),
  );

  const providerMap = ref(
    new Map([
      ['prov-1', { name: 'GitHub', type: 'github' }],
      ['prov-2', { name: 'GitLab', type: 'gitlab' }],
    ]),
  );

  return useTechStackGrouping({
    getLtsInfo: (fw) => {
      if (fw === 'Symfony') return { latestLts: '7.2', releaseDate: '2025-11-01' };
      if (fw === 'Vue') return { latestLts: '3.5', releaseDate: '2025-09-01' };
      return null;
    },
    getVersionMaintenanceStatus: (fw, version) => {
      if (fw === 'Symfony' && version === '6.4') return { status: 'eol', eolDate: '2025-01-01', lastRelease: null };
      if (fw === 'Vue' && version === '2.7') return { status: 'warning', eolDate: null, lastRelease: '2024-06-01' };
      return { status: 'active', eolDate: null, lastRelease: null };
    },
    getVersionReleaseDate: (fw, version) => {
      if (fw === 'Symfony' && version === '6.4') return '2023-11-01';
      if (fw === 'Symfony' && version === '7.2') return '2025-11-01';
      if (fw === 'Vue' && version === '2.7') return '2022-07-01';
      if (fw === 'Vue' && version === '3.5') return '2025-09-01';
      return null;
    },
    isVersionUpToDate: (version, latest) => version === latest,
    projectMap,
    providerMap,
    techStacks: ref(stacks),
  });
}

describe('useTechStackGrouping', () => {
  const sampleStacks: TechStack[] = [
    makeTechStack({ id: '1', framework: 'Symfony', frameworkVersion: '7.2', projectId: 'proj-1' }),
    makeTechStack({ id: '2', framework: 'Symfony', frameworkVersion: '6.4', projectId: 'proj-2' }),
    makeTechStack({ id: '3', framework: 'Vue', frameworkVersion: '3.5', language: 'TypeScript', projectId: 'proj-1' }),
    makeTechStack({ id: '4', framework: 'Vue', frameworkVersion: '2.7', language: 'TypeScript', projectId: 'proj-3' }),
  ];

  it('returns all stacks when no filters applied', () => {
    const { filteredStacks } = createGrouping(sampleStacks);
    expect(filteredStacks.value).toHaveLength(4);
  });

  it('filters by framework', () => {
    const { filterFramework, filteredStacks } = createGrouping(sampleStacks);
    filterFramework.value = 'Symfony';
    expect(filteredStacks.value).toHaveLength(2);
    expect(filteredStacks.value.every((ts) => ts.framework === 'Symfony')).toBe(true);
  });

  it('filters by search term on project name', () => {
    const { filteredStacks, search } = createGrouping(sampleStacks);
    search.value = 'alpha';
    expect(filteredStacks.value).toHaveLength(2);
  });

  it('filters by search term on framework', () => {
    const { filteredStacks, search } = createGrouping(sampleStacks);
    search.value = 'vue';
    expect(filteredStacks.value).toHaveLength(2);
  });

  it('filters by provider', () => {
    const { filterProvider, filteredStacks } = createGrouping(sampleStacks);
    filterProvider.value = 'prov-2';
    expect(filteredStacks.value).toHaveLength(1);
    expect(filteredStacks.value[0].projectId).toBe('proj-3');
  });

  it('filters by status eol', () => {
    const { filterStatus, filteredStacks } = createGrouping(sampleStacks);
    filterStatus.value = 'eol';
    expect(filteredStacks.value).toHaveLength(1);
    expect(filteredStacks.value[0].frameworkVersion).toBe('6.4');
  });

  it('filters by status warning', () => {
    const { filterStatus, filteredStacks } = createGrouping(sampleStacks);
    filterStatus.value = 'warning';
    expect(filteredStacks.value).toHaveLength(1);
    expect(filteredStacks.value[0].frameworkVersion).toBe('2.7');
  });

  it('computes available frameworks sorted', () => {
    const { availableFrameworks } = createGrouping(sampleStacks);
    expect(availableFrameworks.value).toEqual(['Symfony', 'Vue']);
  });

  it('excludes "none" from available frameworks', () => {
    const stacks = [
      ...sampleStacks,
      makeTechStack({ id: '5', framework: 'none', projectId: 'proj-1' }),
    ];
    const { availableFrameworks } = createGrouping(stacks);
    expect(availableFrameworks.value).not.toContain('none');
  });

  it('groups by project (default)', () => {
    const { groupedStacks } = createGrouping(sampleStacks);
    const firstInGroup = groupedStacks.value.filter((r) => r.isFirstInGroup);
    expect(firstInGroup).toHaveLength(3);
  });

  it('groups by framework', () => {
    const { groupBy, groupedStacks } = createGrouping(sampleStacks);
    groupBy.value = 'framework';
    const firstInGroup = groupedStacks.value.filter((r) => r.isFirstInGroup);
    expect(firstInGroup).toHaveLength(2);
    expect(firstInGroup.map((r) => r.projectName).sort()).toEqual(['Symfony', 'Vue']);
  });

  it('groups by provider', () => {
    const { groupBy, groupedStacks } = createGrouping(sampleStacks);
    groupBy.value = 'provider';
    const firstInGroup = groupedStacks.value.filter((r) => r.isFirstInGroup);
    expect(firstInGroup).toHaveLength(2);
  });

  it('toggleSort switches direction on same field', () => {
    const { sortDir, sortField, toggleSort } = createGrouping(sampleStacks);
    expect(sortField.value).toBe('project');
    expect(sortDir.value).toBe('asc');
    toggleSort('project');
    expect(sortDir.value).toBe('desc');
  });

  it('toggleSort switches to new field ascending', () => {
    const { sortDir, sortField, toggleSort } = createGrouping(sampleStacks);
    toggleSort('framework');
    expect(sortField.value).toBe('framework');
    expect(sortDir.value).toBe('asc');
  });

  it('sortIndicator shows arrow for active field', () => {
    const { sortIndicator, toggleSort } = createGrouping(sampleStacks);
    expect(sortIndicator('project')).toBe(' ↑');
    toggleSort('project');
    expect(sortIndicator('project')).toBe(' ↓');
  });

  it('sortIndicator returns empty for inactive field', () => {
    const { sortIndicator } = createGrouping(sampleStacks);
    expect(sortIndicator('framework')).toBe('');
  });

  it('computes health score', () => {
    const { healthScore } = createGrouping(sampleStacks);
    expect(healthScore.value).not.toBeNull();
    expect(healthScore.value!.total).toBe(4);
    expect(healthScore.value!.eol).toBe(1);
    expect(healthScore.value!.warning).toBe(1);
    expect(healthScore.value!.active).toBe(2);
    expect(healthScore.value!.percent).toBe(50);
  });

  it('returns null health score for empty stacks', () => {
    const { healthScore } = createGrouping([]);
    expect(healthScore.value).toBeNull();
  });

  it('computes gap stats', () => {
    const { gapStats } = createGrouping(sampleStacks);
    expect(gapStats.value).not.toBeNull();
    expect(gapStats.value!.cumulated).toBeGreaterThan(0);
    expect(gapStats.value!.average).toBeGreaterThan(0);
    expect(gapStats.value!.median).toBeGreaterThan(0);
  });

  it('returns null gap stats when no outdated stacks', () => {
    const stacks = [
      makeTechStack({ id: '1', framework: 'Symfony', frameworkVersion: '7.2', projectId: 'proj-1' }),
    ];
    const { gapStats } = createGrouping(stacks);
    expect(gapStats.value).toBeNull();
  });

  it('computes provider aggregates', () => {
    const { providerAggregates } = createGrouping(sampleStacks);
    expect(providerAggregates.value).toHaveLength(2);

    const github = providerAggregates.value.find((a) => a.name === 'GitHub');
    expect(github).toBeDefined();
    expect(github!.projectCount).toBe(2);
    expect(github!.frameworks).toHaveLength(2);

    const gitlab = providerAggregates.value.find((a) => a.name === 'GitLab');
    expect(gitlab).toBeDefined();
    expect(gitlab!.projectCount).toBe(1);
  });

  it('sets groupSize correctly for grouped stacks', () => {
    const { groupBy, groupedStacks } = createGrouping(sampleStacks);
    groupBy.value = 'framework';
    const symfonyRows = groupedStacks.value.filter((r) => r.ts.framework === 'Symfony');
    expect(symfonyRows[0].groupSize).toBe(2);
    expect(symfonyRows[0].isFirstInGroup).toBe(true);
    expect(symfonyRows[1].isFirstInGroup).toBe(false);
  });
});
