export type SyncTaskType = 'outdated_dependency' | 'vulnerability' | 'stack_upgrade' | 'new_dependency' | 'stale_pr'
export type SyncTaskSeverity = 'critical' | 'high' | 'medium' | 'low' | 'info'
export type SyncTaskStatus = 'open' | 'acknowledged' | 'resolved' | 'dismissed'

export interface SyncTask {
  id: string
  type: SyncTaskType
  severity: SyncTaskSeverity
  title: string
  description: string
  status: SyncTaskStatus
  metadata: Record<string, unknown>
  projectId: string
  resolvedAt: string | null
  createdAt: string
  updatedAt: string
}

export interface SyncTaskStatsEntry {
  label: string
  count: number
}

export interface SyncTaskStats {
  byType: SyncTaskStatsEntry[]
  bySeverity: SyncTaskStatsEntry[]
  byStatus: SyncTaskStatsEntry[]
}
