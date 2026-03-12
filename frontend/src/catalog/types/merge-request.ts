export type MergeRequestStatus = 'open' | 'merged' | 'closed' | 'draft'

export interface MergeRequest {
  id: string
  externalId: string
  title: string
  description: string | null
  sourceBranch: string
  targetBranch: string
  status: MergeRequestStatus
  author: string
  url: string
  additions: number | null
  deletions: number | null
  reviewers: string[]
  labels: string[]
  mergedAt: string | null
  closedAt: string | null
  projectId: string
  createdAt: string
  updatedAt: string
}
