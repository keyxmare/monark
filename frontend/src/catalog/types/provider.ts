export type ProviderType = 'gitlab' | 'github' | 'bitbucket'
export type ProviderStatus = 'pending' | 'connected' | 'error'

export interface Provider {
  id: string
  name: string
  type: ProviderType
  url: string
  status: ProviderStatus
  lastSyncAt: string | null
  createdAt: string
  updatedAt: string
}

export interface CreateProviderInput {
  name: string
  type: ProviderType
  url: string
  apiToken: string
}

export interface UpdateProviderInput {
  name?: string
  url?: string
  apiToken?: string
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
