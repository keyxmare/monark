import type { SyncTask } from '@/activity/types/sync-task';

export function createSyncTask(overrides?: Partial<SyncTask>): SyncTask {
  return {
    id: 'task-1',
    type: 'outdated_dependency',
    severity: 'medium',
    title: 'Outdated dependency: vue',
    description: 'vue 3.4.0 is outdated. Latest version is 3.5.0.',
    status: 'open',
    metadata: {},
    projectId: 'project-1',
    resolvedAt: null,
    createdAt: '2025-01-01T00:00:00+00:00',
    updatedAt: '2025-01-01T00:00:00+00:00',
    ...overrides,
  };
}
