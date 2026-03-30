import { computed, ref } from 'vue';
import { describe, expect, it } from 'vitest';
import { useDependencyGrouping } from '../useDependencyGrouping';

describe('useDependencyGrouping', () => {
  const deps = computed(() => [
    { id: '1', name: 'axios', isOutdated: true, projectId: 'p1', vulnerabilityCount: 2 },
    { id: '2', name: 'lodash', isOutdated: false, projectId: 'p2', vulnerabilityCount: 0 },
    { id: '3', name: 'axios', isOutdated: false, projectId: 'p2', vulnerabilityCount: 0 },
  ] as never);

  it('groups by name', () => {
    const { groupedDeps } = useDependencyGrouping(deps, (id) => id, ref('name'), ref('asc'));
    const axiosRows = groupedDeps.value.filter(r => r.dep.name === 'axios');
    expect(axiosRows).toHaveLength(2);
    expect(axiosRows[0].groupSize).toBe(2);
    expect(axiosRows[0].isFirstInGroup).toBe(true);
  });

  it('sorts by name asc', () => {
    const { groupedDeps } = useDependencyGrouping(deps, (id) => id, ref('name'), ref('asc'));
    expect(groupedDeps.value[0].dep.name).toBe('axios');
  });
});
