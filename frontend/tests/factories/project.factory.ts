import type { Project } from '@/catalog/types/project';

export function createProject(overrides?: Partial<Project>): Project {
  return {
    id: 'project-1',
    name: 'My Project',
    slug: 'my-project',
    description: 'A sample project',
    repositoryUrl: 'https://github.com/acme/my-project',
    defaultBranch: 'main',
    visibility: 'public',
    ownerId: 'user-1',
    externalId: null,
    providerId: null,
    techStacksCount: 0,
    techStacks: [],
    createdAt: '2025-01-01T00:00:00+00:00',
    updatedAt: '2025-01-01T00:00:00+00:00',
    ...overrides,
  };
}
