export interface TechStack {
  id: string;
  language: string;
  framework: string;
  version: string;
  frameworkVersion: string;
  detectedAt: string;
  projectId: string;
  createdAt: string;
}

export interface CreateTechStackInput {
  language: string;
  framework: string;
  version: string;
  frameworkVersion: string;
  detectedAt: string;
  projectId: string;
}
