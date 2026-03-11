export type PackageManager = 'composer' | 'npm' | 'pip'

export type DependencyType = 'runtime' | 'dev'

export interface Dependency {
  id: string
  name: string
  currentVersion: string
  latestVersion: string
  ltsVersion: string
  packageManager: PackageManager
  type: DependencyType
  isOutdated: boolean
  projectId: string
  repositoryUrl: string | null
  vulnerabilityCount: number
  createdAt: string
  updatedAt: string
}

export interface CreateDependencyInput {
  name: string
  currentVersion: string
  latestVersion: string
  ltsVersion: string
  packageManager: PackageManager
  type: DependencyType
  isOutdated: boolean
  projectId: string
  repositoryUrl?: string | null
}

export interface UpdateDependencyInput {
  name?: string
  currentVersion?: string
  latestVersion?: string
  ltsVersion?: string
  packageManager?: PackageManager
  type?: DependencyType
  isOutdated?: boolean
  repositoryUrl?: string | null
}
