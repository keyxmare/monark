---
id: TASK-010
title: Implement incremental sync for external provider data
status: Done
assignee: []
created_date: '2026-03-12 12:36'
updated_date: '2026-03-12 13:04'
labels:
  - performance
  - backend
  - catalog
dependencies: []
references:
  - backend/src/Catalog/Application/CommandHandler/SyncMergeRequestsHandler.php
  - >-
    backend/src/Catalog/Application/CommandHandler/SyncProjectMetadataHandler.php
  - backend/src/Catalog/Application/CommandHandler/SyncAllProjectsHandler.php
  - backend/src/Catalog/Application/CommandHandler/ScanProjectHandler.php
  - backend/src/Catalog/Domain/Port/GitProviderInterface.php
  - backend/src/Catalog/Infrastructure/GitProvider/GitLabClient.php
  - backend/src/Catalog/Infrastructure/GitProvider/GitHubClient.php
  - backend/src/Catalog/Domain/Model/MergeRequest.php
  - backend/src/Catalog/Domain/Model/Project.php
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Currently, every sync operation fetches ALL data from external providers (GitLab/GitHub) regardless of what has changed. This causes unnecessary API calls, slow sync times, and rate limit pressure.

**Current behavior:**
- `SyncMergeRequestsHandler`: fetches ALL merge requests (state=all) with full pagination — including years-old merged/closed MRs that will never change
- `SyncProjectMetadataHandler`: fetches project metadata every time (lightweight, acceptable)
- `ScanProjectHandler`: deletes ALL TechStack/Dependency records and recreates from scratch every scan
- No `lastSyncedAt` timestamp on Project entities — no way to know when a project was last synced
- GitLab/GitHub APIs return MRs sorted by `updated_at DESC` but we never leverage this for early-exit
- `Provider.lastSyncAt` only tracks connection test, not actual sync operations

**Goal:** Only fetch data that has changed since the last sync. Skip obsolete/terminal MRs. Reduce API calls by 80%+ on subsequent syncs.

**Key design constraints:**
- GitLab API: supports `updated_after` parameter on `/merge_requests`
- GitHub API: supports `sort=updated&direction=desc` (already used) + `since` parameter
- Both APIs return results sorted by `updated_at DESC`, enabling early-exit pagination
- Terminal MR states (merged, closed) rarely change — can be skipped after initial sync
- TechStack/Dependency delete-and-recreate is acceptable (scan reads files, not API-heavy)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Project entity has a `lastSyncedAt` field updated after each successful sync
- [x] #2 MR sync uses `updated_after`/`since` parameter to only fetch MRs changed since `lastSyncedAt`
- [x] #3 MR sync stops paginating when it encounters MRs older than `lastSyncedAt` (early-exit)
- [x] #4 First sync (no `lastSyncedAt`) still fetches all data as before
- [ ] #5 MRs in terminal state (merged/closed) older than 30 days are excluded from regular sync
- [x] #6 GitProviderInterface.listMergeRequests accepts an optional `updatedAfter` DateTime parameter
- [x] #7 Both GitLabClient and GitHubClient implement the `updatedAfter` filtering
- [ ] #8 Existing tests pass, new tests cover incremental sync paths
- [x] #9 A full re-sync can still be triggered explicitly (force flag bypasses incremental logic)
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
