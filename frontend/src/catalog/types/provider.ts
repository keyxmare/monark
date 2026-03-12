export type ProviderType = 'gitlab' | 'github' | 'bitbucket'
export type ProviderStatus = 'pending' | 'connected' | 'error'

export interface Provider {
  id: string
  name: string
  type: ProviderType
  url: string
  username: string | null
  status: ProviderStatus
  projectsCount: number
  lastSyncAt: string | null
  createdAt: string
  updatedAt: string
}

export interface CreateProviderInput {
  name: string
  type: ProviderType
  url: string
  apiToken?: string
  username?: string
}

export interface UpdateProviderInput {
  name?: string
  url?: string
  apiToken?: string
  username?: string
}

export interface RemoteProject {
  externalId: string
  name: string
  slug: string
  description: string | null
  repositoryUrl: string
  defaultBranch: string
  visibility: string
  avatarUrl: string | null
  alreadyImported: boolean
  localProjectId: string | null
}

export interface ImportProjectsInput {
  projects: Array<{
    externalId: string
    name: string
    slug: string
    description: string | null
    repositoryUrl: string
    defaultBranch: string
    visibility: string
  }>
}
