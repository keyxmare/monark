export type SyncStepName = 'sync_projects' | 'sync_coverage' | 'sync_versions' | 'scan_cve';
export type SyncStatus = 'running' | 'completed' | 'failed';

export interface GlobalSyncState {
  syncId: string;
  status: SyncStatus;
  currentStep: 1 | 2 | 3 | 4;
  currentStepName: SyncStepName;
  stepProgress: number;
  stepTotal: number;
  completedSteps: SyncStepName[];
  message?: string;
}

export const STEP_LABELS: Record<SyncStepName, string> = {
  sync_projects: 'Sync Projets',
  sync_coverage: 'Sync Coverage',
  sync_versions: 'Sync Versions',
  scan_cve: 'Scan CVE',
};

export const STEP_ORDER: SyncStepName[] = ['sync_projects', 'sync_coverage', 'sync_versions', 'scan_cve'];
