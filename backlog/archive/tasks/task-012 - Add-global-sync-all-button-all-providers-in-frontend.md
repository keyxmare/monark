---
id: TASK-012
title: Add global sync-all button (all providers) in frontend
status: Done
assignee: []
created_date: '2026-03-12 15:12'
updated_date: '2026-03-12 15:15'
labels:
  - frontend
  - catalog
  - sync
dependencies: []
references:
  - backend/src/Catalog/Presentation/Controller/SyncAllProjectsController.php
  - frontend/src/catalog/pages/ProviderList.vue
  - frontend/src/catalog/pages/ProviderDetail.vue
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
The backend already exposes `POST /api/catalog/sync-all` to trigger sync for ALL providers at once. Currently, the frontend only has a sync button per provider (on ProviderDetail.vue). 

We need a global "Sync All" action accessible from a higher-level page (e.g. the providers list page or the dashboard) that triggers a full synchronization across all providers.

## Scope

### Frontend
- Add `syncAll()` method to `provider.service.ts` calling `POST /catalog/sync-all` (no provider ID)
- Add `syncAllProviders()` to the provider store
- Add a "Tout synchroniser" button on `ProviderList.vue` (header area, next to "Add Provider")
- Support `?force=1` query param for force sync
- Show success/error feedback banner (reuse pattern from ProviderDetail.vue)

### Backend
- Already done: `SyncAllProjectsController::syncAll()` handles `POST /api/catalog/sync-all` with optional `?force` param
- No backend changes needed
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Global sync-all button visible on ProviderList page
- [x] #2 Clicking triggers POST /api/catalog/sync-all
- [x] #3 Loading state shown during sync
- [x] #4 Success banner shows number of projects synced
- [x] #5 Error banner shown on failure
- [x] #6 Force sync option available
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Added global "Tout synchroniser" button on ProviderList.vue that calls POST /api/catalog/sync-all. Includes syncAllGlobal() in service and store, loading state, and success/error feedback banner. Commit: 5626333.
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
