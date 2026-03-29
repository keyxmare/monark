export interface TechStack {
  id: string;
  language: string;
  framework: string;
  version: string;
  frameworkVersion: string;
  detectedAt: string;
  projectId: string;
  createdAt: string;
  latestLts: string | null;
  ltsGap: string | null;
  maintenanceStatus: 'active' | 'warning' | 'eol' | null;
  eolDate: string | null;
  versionSyncedAt: string | null;
}

export interface CreateTechStackInput {
  language: string;
  framework: string;
  version: string;
  frameworkVersion: string;
  detectedAt: string;
  projectId: string;
}
