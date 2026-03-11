export type PipelineStatus = 'pending' | 'running' | 'success' | 'failed'

export interface Pipeline {
  id: string
  externalId: string
  ref: string
  status: PipelineStatus
  duration: number
  startedAt: string
  finishedAt: string | null
  projectId: string
  createdAt: string
}

export interface CreatePipelineInput {
  externalId: string
  ref: string
  status: PipelineStatus
  duration: number
  startedAt: string
  finishedAt?: string
  projectId: string
}
