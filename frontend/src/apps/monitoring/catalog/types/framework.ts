export interface Framework {
  id: string;
  name: string;
  version: string;
  detectedAt: string;
  latestLts: string | null;
  ltsGap: string | null;
  maintenanceStatus: 'active' | 'warning' | 'eol' | null;
  eolDate: string | null;
  versionSyncedAt: string | null;
  languageId: string;
  projectId: string;
  createdAt: string;
  updatedAt: string;
}

export interface CreateFrameworkInput {
  name: string;
  version: string;
  detectedAt: string;
  languageId: string;
  projectId: string;
}
