export const Layout = {
  Auth: 'auth',
  Dashboard: 'dashboard',
} as const;

export const SyncStatus = {
  Completed: 'completed',
  Failed: 'failed',
  Running: 'running',
} as const;

export const MergeRequestState = {
  Closed: 'closed',
  Draft: 'draft',
  Merged: 'merged',
  Open: 'open',
} as const;

export const ImportStatus = {
  Error: 'error',
  Imported: 'imported',
  Importing: 'importing',
  Pending: 'pending',
} as const;
