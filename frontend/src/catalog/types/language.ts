export interface Language {
  id: string;
  name: string;
  version: string;
  detectedAt: string;
  eolDate: string | null;
  maintenanceStatus: 'active' | 'eol' | null;
  projectId: string;
  createdAt: string;
  updatedAt: string;
}

export interface CreateLanguageInput {
  name: string;
  version: string;
  detectedAt: string;
  projectId: string;
}
