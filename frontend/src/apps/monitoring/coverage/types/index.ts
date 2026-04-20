export interface CoverageSummary {
  averageCoverage: number | null;
  totalProjects: number;
  coveredProjects: number;
  aboveThreshold: number;
  belowThreshold: number;
  trend: number | null;
}

export interface CoverageJobDetail {
  name: string;
  percent: number;
  trend: number | null;
}

export interface CoverageProject {
  projectId: string;
  projectName: string;
  projectSlug: string;
  coveragePercent: number | null;
  trend: number | null;
  source: string | null;
  commitHash: string | null;
  ref: string | null;
  syncedAt: string | null;
  jobs: CoverageJobDetail[];
}

export interface CoverageDashboard {
  summary: CoverageSummary;
  projects: CoverageProject[];
}

export interface CoverageSnapshot {
  commitHash: string;
  coveragePercent: number;
  source: string;
  ref: string;
  pipelineId: string | null;
  jobs: Array<{ name: string; percent: number }> | null;
  createdAt: string;
}

export interface ProjectCoverageHistory {
  project: { id: string; name: string; slug: string };
  snapshots: CoverageSnapshot[];
}
