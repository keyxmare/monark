import type { Dependency } from '@/dependency/types/dependency';

export function createDependency(overrides?: Partial<Dependency>): Dependency {
  return {
    id: 'dep-1',
    name: 'vue',
    currentVersion: '3.4.0',
    latestVersion: '3.5.0',
    ltsVersion: '3.4.0',
    packageManager: 'npm',
    type: 'runtime',
    isOutdated: true,
    projectId: 'project-1',
    repositoryUrl: 'https://github.com/vuejs/core',
    vulnerabilityCount: 0,
    registryStatus: 'synced',
    createdAt: '2025-01-01T00:00:00+00:00',
    updatedAt: '2025-01-01T00:00:00+00:00',
    currentVersionReleasedAt: '2024-06-01T00:00:00+00:00',
    latestVersionReleasedAt: '2025-01-01T00:00:00+00:00',
    ...overrides,
  };
}
