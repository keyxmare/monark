import type { ApiResponse } from '@/hub/shared/types';
import { api } from '@/hub/shared/utils/api';

export interface LanguageShare {
  name: string;
  projectsCount: number;
}

export interface HostShare {
  connected: boolean;
  label: string;
  projectsCount: number;
  type: string;
}

export interface MostActiveProjectEntry {
  id: string;
  name: string;
  slug: string;
  commits: number;
}

export interface WeakestCoverageProjectEntry {
  id: string;
  name: string;
  slug: string;
  coveragePercent: number;
}

export interface OutdatedDependencyOccurrence {
  projectName: string;
  projectSlug: string;
  currentVersion: string;
  latestVersion: string;
}

export interface TopOutdatedDependencyEntry {
  name: string;
  projectsCount: number;
  occurrences: OutdatedDependencyOccurrence[];
}

export interface ProjectDriftEntry {
  name: string;
  slug: string;
  total: number;
  upToDate: number;
  drift: number;
}

export interface DashboardSummary {
  projects: number;
  commits30d: number;
  activeBranches30d: number;
  dependenciesTracked: number;
  vulnerabilities: number;
  coveragePercent: null | number;
  coverageHealthPercent: null | number;
  languages: LanguageShare[];
  hosts: HostShare[];
  mostActive: MostActiveProjectEntry[];
  weakestCoverage: WeakestCoverageProjectEntry[];
  topOutdatedDependencies: TopOutdatedDependencyEntry[];
  projectDrift: ProjectDriftEntry[];
}

interface LanguageShareResponse {
  name: string;
  projects_count: number;
}

interface HostShareResponse {
  connected: boolean;
  label: string;
  projects_count: number;
  type: string;
}

interface DashboardResponse {
  projects: number;
  commits_30d: number;
  active_branches_30d: number;
  dependencies_tracked: number;
  vulnerabilities: number;
  coverage_percent: null | number;
  coverage_health_percent: null | number;
  languages: LanguageShareResponse[];
  hosts: HostShareResponse[];
  most_active: MostActiveProjectEntry[];
  weakest_coverage: WeakestCoverageProjectEntry[];
  top_outdated_dependencies: TopOutdatedDependencyEntry[];
  project_drift: ProjectDriftEntry[];
}

export const dashboardService = {
  async getDashboard(): Promise<DashboardSummary> {
    const body = await api.get<ApiResponse<DashboardResponse>>('/monitoring/activity/dashboard');
    const d = body.data;
    return {
      projects: d.projects,
      commits30d: d.commits_30d,
      activeBranches30d: d.active_branches_30d,
      dependenciesTracked: d.dependencies_tracked,
      vulnerabilities: d.vulnerabilities,
      coveragePercent: d.coverage_percent,
      coverageHealthPercent: d.coverage_health_percent ?? null,
      languages: d.languages.map((l) => ({ name: l.name, projectsCount: l.projects_count })),
      hosts: d.hosts.map((h) => ({
        type: h.type,
        label: h.label,
        connected: h.connected,
        projectsCount: h.projects_count,
      })),
      mostActive: d.most_active ?? [],
      weakestCoverage: d.weakest_coverage ?? [],
      topOutdatedDependencies: d.top_outdated_dependencies ?? [],
      projectDrift: d.project_drift ?? [],
    };
  },
};
