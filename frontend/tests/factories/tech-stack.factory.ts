import type { TechStack } from '@/catalog/types/tech-stack';

export function createTechStack(overrides?: Partial<TechStack>): TechStack {
  return {
    id: 'stack-1',
    language: 'TypeScript',
    framework: 'Vue',
    version: '5.7',
    frameworkVersion: '3.5',
    detectedAt: '2025-01-01T00:00:00+00:00',
    projectId: 'project-1',
    createdAt: '2025-01-01T00:00:00+00:00',
    latestLts: null,
    ltsGap: null,
    maintenanceStatus: null,
    eolDate: null,
    versionSyncedAt: null,
    ...overrides,
  };
}
