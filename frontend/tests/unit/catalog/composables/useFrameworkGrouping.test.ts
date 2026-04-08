import { describe, expect, it } from 'vitest';
import { ref } from 'vue';
import type { Framework } from '@/catalog/types/framework';
import { useFrameworkGrouping } from '@/catalog/composables/useFrameworkGrouping';

function fw(overrides: Partial<Framework> = {}): Framework {
  return {
    id: overrides.id ?? 'fw-' + Math.random(),
    projectId: overrides.projectId ?? 'p1',
    name: overrides.name ?? 'Symfony',
    version: overrides.version ?? '7.2',
    latestLts: overrides.latestLts ?? '7.1',
    ltsGap: overrides.ltsGap ?? '',
    maintenanceStatus: overrides.maintenanceStatus ?? 'ok',
  } as Framework;
}

function setup(items: Framework[]) {
  const frameworks = ref(items);
  const projectMap = ref(
    new Map<string, { name: string; providerId: null | string }>([
      ['p1', { name: 'Monark', providerId: 'prov-1' }],
      ['p2', { name: 'Zebra', providerId: 'prov-2' }],
      ['p3', { name: 'Apple', providerId: null }],
    ]),
  );
  const providerMap = ref(
    new Map<string, { name: string; type: string }>([
      ['prov-1', { name: 'GitHub', type: 'github' }],
      ['prov-2', { name: 'GitLab', type: 'gitlab' }],
    ]),
  );
  return useFrameworkGrouping({ frameworks, projectMap, providerMap });
}

describe('useFrameworkGrouping', () => {
  describe('toggleSort + sortIndicator', () => {
    it('sets ascending dir when changing field and toggles when same', () => {
      const { toggleSort, sortField, sortDir, sortIndicator } = setup([]);

      toggleSort('framework');
      expect(sortField.value).toBe('framework');
      expect(sortDir.value).toBe('asc');
      expect(sortIndicator('framework')).toBe(' ↑');
      expect(sortIndicator('project')).toBe('');

      toggleSort('framework');
      expect(sortDir.value).toBe('desc');
      expect(sortIndicator('framework')).toBe(' ↓');
    });
  });

  describe('availableFrameworks', () => {
    it('returns unique framework names excluding "none", sorted', () => {
      const { availableFrameworks } = setup([
        fw({ name: 'Vue' }),
        fw({ name: 'Symfony' }),
        fw({ name: 'none' }),
        fw({ name: 'Vue' }),
      ]);
      expect(availableFrameworks.value).toEqual(['Symfony', 'Vue']);
    });
  });

  describe('availableProviders', () => {
    it('returns providers from providerMap', () => {
      const { availableProviders } = setup([]);
      const names = availableProviders.value.map((p) => p.name).sort();
      expect(names).toEqual(['GitHub', 'GitLab']);
    });
  });

  describe('filteredFrameworks', () => {
    it('filters by search on project name or framework name', () => {
      const { filteredFrameworks, search } = setup([
        fw({ projectId: 'p1', name: 'Symfony' }),
        fw({ projectId: 'p2', name: 'Vue' }),
      ]);
      search.value = 'zebra';
      expect(filteredFrameworks.value).toHaveLength(1);
      expect(filteredFrameworks.value[0].projectId).toBe('p2');

      search.value = 'symf';
      expect(filteredFrameworks.value).toHaveLength(1);
      expect(filteredFrameworks.value[0].name).toBe('Symfony');

      search.value = 'nothing matches';
      expect(filteredFrameworks.value).toHaveLength(0);
    });

    it('filters by filterFramework', () => {
      const { filteredFrameworks, filterFramework } = setup([
        fw({ name: 'Vue' }),
        fw({ name: 'Symfony' }),
      ]);
      filterFramework.value = 'Vue';
      expect(filteredFrameworks.value.map((f) => f.name)).toEqual(['Vue']);
    });

    it('filters by filterStatus', () => {
      const { filteredFrameworks, filterStatus } = setup([
        fw({ maintenanceStatus: 'eol' }),
        fw({ maintenanceStatus: 'ok' }),
      ]);
      filterStatus.value = 'eol';
      expect(filteredFrameworks.value).toHaveLength(1);
    });

    it('filters by filterProvider via projectMap', () => {
      const { filteredFrameworks, filterProvider } = setup([
        fw({ projectId: 'p1' }),
        fw({ projectId: 'p2' }),
        fw({ projectId: 'p3' }),
      ]);
      filterProvider.value = 'prov-1';
      expect(filteredFrameworks.value.map((f) => f.projectId)).toEqual(['p1']);
    });
  });

  describe('healthScore', () => {
    it('returns null when no frameworks', () => {
      const { healthScore } = setup([]);
      expect(healthScore.value).toBeNull();
    });

    it('computes percent active', () => {
      const { healthScore } = setup([
        fw({ maintenanceStatus: 'ok' }),
        fw({ maintenanceStatus: 'ok' }),
        fw({ maintenanceStatus: 'warning' }),
        fw({ maintenanceStatus: 'eol' }),
      ]);
      expect(healthScore.value).toEqual({
        active: 2,
        eol: 1,
        warning: 1,
        total: 4,
        percent: 50,
      });
    });
  });

  describe('providerAggregates', () => {
    it('groups frameworks by provider and collapses versions to min/max', () => {
      const { providerAggregates } = setup([
        fw({ projectId: 'p1', name: 'Symfony', version: '6.4' }),
        fw({ projectId: 'p1', name: 'Symfony', version: '7.2' }),
        fw({ projectId: 'p2', name: 'Vue', version: '3.5' }),
        fw({ projectId: 'p3', name: 'React', version: '18' }),
        fw({ projectId: 'p1', name: 'none', version: '' }),
      ]);

      const gh = providerAggregates.value.find((p) => p.id === 'prov-1');
      expect(gh).toBeDefined();
      expect(gh!.projectCount).toBe(1);
      const symfony = gh!.frameworks.find((f) => f.name === 'Symfony');
      expect(symfony).toEqual({ name: 'Symfony', min: '6.4', max: '7.2' });
    });

    it('skips frameworks whose project has no provider', () => {
      const { providerAggregates } = setup([fw({ projectId: 'p3', name: 'React' })]);
      expect(providerAggregates.value).toHaveLength(0);
    });
  });

  describe('groupedFrameworks', () => {
    it('groups by project by default and flags first row in group', () => {
      const { groupedFrameworks } = setup([
        fw({ projectId: 'p1', name: 'Symfony' }),
        fw({ projectId: 'p1', name: 'Vue' }),
        fw({ projectId: 'p2', name: 'React' }),
      ]);
      const firsts = groupedFrameworks.value.filter((r) => r.isFirstInGroup);
      expect(firsts).toHaveLength(2);
      expect(groupedFrameworks.value[0].projectName).toBeDefined();
    });

    it('supports grouping by framework', () => {
      const { groupedFrameworks, groupBy } = setup([
        fw({ projectId: 'p1', name: 'Vue' }),
        fw({ projectId: 'p2', name: 'Vue' }),
        fw({ projectId: 'p1', name: 'Symfony' }),
      ]);
      groupBy.value = 'framework';
      const uniqueGroups = new Set(groupedFrameworks.value.map((r) => r.groupIndex));
      expect(uniqueGroups.size).toBe(2);
    });

    it('supports grouping by provider', () => {
      const { groupedFrameworks, groupBy } = setup([
        fw({ projectId: 'p1', name: 'Symfony' }),
        fw({ projectId: 'p2', name: 'Vue' }),
      ]);
      groupBy.value = 'provider';
      expect(groupedFrameworks.value).toHaveLength(2);
    });

    it('sorts by ltsGap using worst maintenance status', () => {
      const { groupedFrameworks, sortField } = setup([
        fw({ projectId: 'p1', name: 'A', ltsGap: '1y', maintenanceStatus: 'ok' }),
        fw({ projectId: 'p2', name: 'B', ltsGap: '3y', maintenanceStatus: 'eol' }),
        fw({ projectId: 'p3', name: 'C', ltsGap: '2y', maintenanceStatus: 'warning' }),
      ]);
      sortField.value = 'ltsGap';
      const first = groupedFrameworks.value[0];
      expect(first.fw.maintenanceStatus).toBe('eol');
    });

    it('pushes groups without ltsGap to the end when sorting by ltsGap', () => {
      const { groupedFrameworks, sortField } = setup([
        fw({ projectId: 'p1', name: 'A', ltsGap: '', maintenanceStatus: 'ok' }),
        fw({ projectId: 'p2', name: 'B', ltsGap: '1y', maintenanceStatus: 'warning' }),
      ]);
      sortField.value = 'ltsGap';
      const last = groupedFrameworks.value[groupedFrameworks.value.length - 1];
      expect(last.fw.ltsGap).toBe('');
    });

    it('respects sortDir desc', () => {
      const { groupedFrameworks, sortField, sortDir } = setup([
        fw({ projectId: 'p1', name: 'A' }),
        fw({ projectId: 'p2', name: 'B' }),
      ]);
      sortField.value = 'framework';
      sortDir.value = 'desc';
      expect(groupedFrameworks.value[0].projectId).not.toBe(
        groupedFrameworks.value[groupedFrameworks.value.length - 1].projectId,
      );
    });
  });
});
