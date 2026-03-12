---
id: TASK-014
title: Add real-time sync progress tracking with toast feedback
status: Done
assignee: []
created_date: '2026-03-12 15:21'
updated_date: '2026-03-12 15:50'
labels:
  - fullstack
  - catalog
  - sync
  - ux
dependencies:
  - TASK-013
  - TASK-015
references:
  - backend/src/Catalog/Application/CommandHandler/SyncAllProjectsHandler.php
  - backend/src/Catalog/Application/CommandHandler/SyncMergeRequestsHandler.php
  - backend/src/Catalog/Domain/Event/MergeRequestsSyncedEvent.php
  - frontend/src/catalog/pages/ProviderList.vue
  - frontend/src/catalog/pages/ProviderDetail.vue
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Track sync job progress in backend and display real-time feedback in a toast notification via Mercure SSE.

## Context
Currently, sync-all triggers async commands but provides no progress feedback. The user sees a static "Sync started for X project(s)" banner with no updates.

## Scope

### Backend — Catalog context
- **SyncJob entity** (`src/Catalog/Domain/Entity/SyncJob.php`):
  - `id` (UUID), `totalProjects` (int), `completedProjects` (int, default 0)
  - `status` (enum: running, completed, failed), `providerId` (nullable UUID)
  - `createdAt`, `completedAt` (nullable)
  - Methods: `incrementCompleted()` (auto-completes when completedProjects === totalProjects)

- **Migration**: create `catalog_sync_jobs` table

- **SyncAllProjectsHandler changes**:
  - Create and persist a SyncJob before dispatching commands
  - Pass `syncJobId` to each `SyncMergeRequestsCommand` (last command per project)
  - Return `SyncJobOutput` with the job `id` included

- **SyncMergeRequestsHandler changes**:
  - After sync complete, dispatch `ProjectSyncCompletedEvent` with `syncJobId`

- **Event listener** (`IncrementSyncJobProgressListener`):
  - On `ProjectSyncCompletedEvent`: increment SyncJob.completedProjects
  - **Publish Mercure update** to topic `/sync-jobs/{id}` with `{ completedProjects, totalProjects, status }`
  - Mark job as completed when all projects done

- **Query endpoint**: `GET /api/catalog/sync-jobs/{id}` → returns current state (fallback, not primary)

### Frontend — Catalog
- **Mercure composable** (`frontend/src/shared/composables/useMercure.ts`):
  - Generic `subscribe(topic)` → returns reactive ref updated on each SSE event
  - Auto-close EventSource on component unmount

- **Sync trigger flow** (ProviderList + ProviderDetail):
  - On sync response, extract `jobId`
  - Call `toastStore.addToast({ variant: 'progress', title: 'Synchronisation', progress: { current: 0, total: N } })`
  - Subscribe to Mercure topic `/sync-jobs/{jobId}`
  - On each SSE event: `toastStore.updateToast(id, { progress: { current, total } })`
  - When status === completed: update toast to success variant, auto-dismiss after 5s
  - When status === failed: update toast to error variant
  - Close EventSource on completion or manual toast close

### Dependencies
- Requires TASK-013 (toast component)
- Requires TASK-015 (Mercure infrastructure)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 SyncJob entity persists in database with progress tracking
- [x] #2 SyncAllProjectsHandler creates a SyncJob and returns its ID
- [x] #3 Progress increments as each project sync completes
- [x] #4 Mercure publishes update on each progress increment to /sync-jobs/{id}
- [x] #5 Frontend subscribes to Mercure topic and receives real-time updates
- [x] #6 Toast shows X/Y projects synced with progress bar
- [x] #7 Toast auto-dismisses 5s after completion
- [x] #8 Toast can be manually closed at any time (closes EventSource)
- [x] #9 Works for both global sync-all and per-provider sync
- [x] #10 GET /api/catalog/sync-jobs/{id} available as fallback
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
SyncJob entity (catalog_sync_jobs) tracks totalProjects/completedProjects/status. SyncAllProjectsHandler creates job and passes syncJobId to SyncMergeRequestsCommand. On completion, ProjectSyncCompletedEvent dispatched → IncrementSyncJobProgressListener increments and publishes to Mercure /sync-jobs/{id}. Frontend useSyncProgress composable subscribes via useMercure and updates toast progress in real-time. Both ProviderList and ProviderDetail use it. GET /api/catalog/sync-jobs/{id} as fallback. Commit: cb797a0.
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
