export type PackageManager = 'composer' | 'npm' | 'pip';

export type DependencyType = 'runtime' | 'dev';

export type RegistryStatus = 'pending' | 'synced' | 'not_found';

export interface Dependency {
  id: string;
  name: string;
  currentVersion: string;
  latestVersion: string;
  ltsVersion: string;
  packageManager: PackageManager;
  type: DependencyType;
  isOutdated: boolean;
  projectId: string;
  repositoryUrl: string | null;
  vulnerabilityCount: number;
  registryStatus: RegistryStatus;
  createdAt: string;
  updatedAt: string;
  currentVersionReleasedAt: string | null;
  latestVersionReleasedAt: string | null;
}

export interface CreateDependencyInput {
  name: string;
  currentVersion: string;
  latestVersion: string;
  ltsVersion: string;
  packageManager: PackageManager;
  type: DependencyType;
  isOutdated: boolean;
  projectId: string;
  repositoryUrl?: string | null;
}

export interface UpdateDependencyInput {
  name?: string;
  currentVersion?: string;
  latestVersion?: string;
  ltsVersion?: string;
  packageManager?: PackageManager;
  type?: DependencyType;
  isOutdated?: boolean;
  repositoryUrl?: string | null;
}

export type SortField = 'name' | 'project' | 'status' | 'vulnerabilities';

export interface DependencyGroup {
  name: string;
  deps: Dependency[];
  groupIndex: number;
  outdatedCount: number;
  vulnCount: number;
}

export interface GroupedDepRow {
  dep: Dependency;
  groupIndex: number;
  groupSize: number;
  isFirstInGroup: boolean;
  projectId: string;
  projectName: string;
}

export interface HealthScore {
  total: number;
  upToDate: number;
  outdated: number;
  totalVulns: number;
  percent: number;
}

export interface DepGapStats {
  average: number;
  median: number;
  cumulated: number;
}

export interface DependencyFilters {
  search: string;
  packageManager: string;
  type: string;
  status: string;
  projectId: string;
}

export type FormState<T> =
  | { status: 'idle' }
  | { status: 'submitting' }
  | { status: 'success'; data: T }
  | { status: 'error'; message: string };
