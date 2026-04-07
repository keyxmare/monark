export interface DebtTimelinePoint {
  snapshotId: string;
  commitSha: string;
  snapshotDate: string;
  source: 'live' | 'backfill';
  totalDeps: number;
  outdatedCount: number;
  vulnerableCount: number;
  majorGapCount: number;
  minorGapCount: number;
  patchGapCount: number;
  ltsGapCount: number;
  debtScore: number;
}

export interface BackfillRequest {
  since: string;
  until: string;
  intervalDays: number;
}
