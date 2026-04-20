export type ProjectVisibility = 'public' | 'private';

export interface TechStackSummary {
  language: string;
  framework: string | null;
  version: string | null;
}

export interface RuntimeSummary {
  name: string;
  productKey: string;
  minVersion: string | null;
  latestVersion: string | null;
  ltsVersion: string | null;
}

export interface FrameworkLagSummary {
  major: number;
  minor: number;
  patch: number;
  upToDate: number;
  unknown: number;
}

export interface VulnerabilityBreakdown {
  critical: number;
  high: number;
  medium: number;
  low: number;
}

export interface CoverageJob {
  name: string;
  percent: number;
}

export interface Project {
  id: string;
  name: string;
  slug: string;
  description: string | null;
  repositoryUrl: string;
  defaultBranch: string;
  visibility: ProjectVisibility;
  ownerId: string;
  externalId: string | null;
  providerId: string | null;
  techStacksCount: number;
  techStacks: TechStackSummary[];
  runtimes: RuntimeSummary[];
  frameworkLag: FrameworkLagSummary;
  coveragePercent: number | null;
  coverageJobs: CoverageJob[];
  dependenciesCount: number;
  outdatedDependenciesCount: number;
  vulnerabilitiesCount: number;
  vulnerabilitiesBySeverity: VulnerabilityBreakdown;
  lastActivityAt: string | null;
  lastCommitSha: string | null;
  commitsLast30d: number;
  commitsDailySeries: number[];
  createdAt: string;
  updatedAt: string;
}

export interface CreateProjectInput {
  name: string;
  slug: string;
  description?: string;
  repositoryUrl: string;
  defaultBranch: string;
  visibility: ProjectVisibility;
  ownerId: string;
}

export interface UpdateProjectInput {
  name?: string;
  slug?: string;
  description?: string;
  repositoryUrl?: string;
  defaultBranch?: string;
  visibility?: ProjectVisibility;
}

export interface ScanResult {
  stacksDetected: number;
  dependenciesDetected: number;
  stacks: Array<{
    language: string;
    framework: string;
    version: string;
    frameworkVersion: string;
  }>;
  dependencies: Array<{
    name: string;
    version: string;
    packageManager: string;
    type: string;
  }>;
}
