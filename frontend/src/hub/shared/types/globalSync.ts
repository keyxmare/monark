export type SyncStepName =
  | 'scan_cve'
  | 'sync_coverage'
  | 'sync_dependencies'
  | 'sync_frameworks'
  | 'sync_projects';
export type SyncStatus = 'completed' | 'failed' | 'running';

export interface GlobalSyncState {
  syncId: string;
  status: SyncStatus;
  currentStep: 1 | 2 | 3 | 4 | 5;
  currentStepName: SyncStepName;
  stepProgress: number;
  stepTotal: number;
  completedSteps: SyncStepName[];
  message?: string;
}

export const STEP_LABELS: Record<SyncStepName, string> = {
  scan_cve: 'Scan CVE',
  sync_coverage: 'Sync Coverage',
  sync_dependencies: 'Sync Dépendances',
  sync_frameworks: 'Sync Frameworks',
  sync_projects: 'Sync Projets',
};

export const STEP_ORDER: SyncStepName[] = [
  'sync_projects',
  'sync_coverage',
  'sync_dependencies',
  'sync_frameworks',
  'scan_cve',
];
