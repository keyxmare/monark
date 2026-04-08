import { ref } from 'vue';
import { describe, expect, it } from 'vitest';
import type { Dependency } from '@/dependency/types';
import { useDependencyFilters } from '../useDependencyFilters';

const makeDepLodash = (overrides = {}): Dependency => ({
  id: '1',
  name: 'lodash',
  packageManager: 'npm',
  type: 'runtime',
  isOutdated: false,
  projectId: 'p1',
  vulnerabilityCount: 0,
  currentVersion: '4.0.0',
  latestVersion: '4.0.0',
  ltsVersion: '',
  registryStatus: 'synced',
  repositoryUrl: null,
  createdAt: '',
  updatedAt: '',
  currentVersionReleasedAt: null,
  latestVersionReleasedAt: null,
  ...overrides,
});

describe('useDependencyFilters', () => {
  it('filters by status outdated', () => {
    const deps = ref([makeDepLodash(), makeDepLodash({ id: '2', isOutdated: true })]);
    const { filters, filteredDeps } = useDependencyFilters(deps, ref(new Map()));
    filters.value.status = 'outdated';
    expect(filteredDeps.value).toHaveLength(1);
    expect(filteredDeps.value[0].id).toBe('2');
  });

  it('filters by packageManager', () => {
    const deps = ref([makeDepLodash(), makeDepLodash({ id: '2', packageManager: 'composer' })]);
    const { filters, filteredDeps } = useDependencyFilters(deps, ref(new Map()));
    filters.value.packageManager = 'composer';
    expect(filteredDeps.value).toHaveLength(1);
  });
});
