import type { MergeRequest } from '@/catalog/types/merge-request';

export function createMergeRequest(overrides?: Partial<MergeRequest>): MergeRequest {
  return {
    id: 'mr-1',
    externalId: '101',
    title: 'feat: add user auth',
    description: 'Implements user authentication flow',
    sourceBranch: 'feature/auth',
    targetBranch: 'main',
    status: 'open',
    author: 'johndoe',
    url: 'https://github.com/acme/my-project/pull/101',
    additions: 150,
    deletions: 30,
    reviewers: [],
    labels: [],
    mergedAt: null,
    closedAt: null,
    projectId: 'project-1',
    createdAt: '2025-01-01T00:00:00+00:00',
    updatedAt: '2025-01-01T00:00:00+00:00',
    ...overrides,
  };
}
