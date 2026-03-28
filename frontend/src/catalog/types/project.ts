export type ProjectVisibility = 'public' | 'private';

export interface TechStackSummary {
  language: string;
  framework: string | null;
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
