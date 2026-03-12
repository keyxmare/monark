---
id: TASK-011
title: Add pagination to remote projects listing per provider
status: Done
assignee: []
created_date: '2026-03-12 12:42'
updated_date: '2026-03-12 13:04'
labels:
  - bug
  - backend
  - frontend
  - catalog
dependencies: []
references:
  - backend/src/Catalog/Application/QueryHandler/ListRemoteProjectsHandler.php
  - backend/src/Catalog/Presentation/Controller/ListRemoteProjectsController.php
  - backend/src/Catalog/Application/Query/ListRemoteProjectsQuery.php
  - backend/src/Catalog/Application/DTO/RemoteProjectOutput.php
  - backend/src/Catalog/Domain/Port/GitProviderInterface.php
  - frontend/src/catalog/pages/ProviderDetail.vue
  - frontend/src/catalog/stores/provider.ts
  - frontend/src/catalog/services/provider.service.ts
  - backend/src/Catalog/Presentation/Controller/ListProjectsController.php
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
When viewing a provider's remote projects, only the first page is displayed (20 for GitLab, 30 for GitHub). There are no pagination controls and no way to see or import projects beyond the first page.

**Root cause:**
The backend pagination plumbing exists end-to-end (controller accepts `page`/`per_page`, query forwards them, handler passes to Git client, Git clients send to API) but the **return path is broken**:
- `ListRemoteProjectsHandler` returns `list<RemoteProjectOutput>` (raw array) instead of a DTO wrapping `PaginatedOutput`
- `ListRemoteProjectsController` returns `ApiResponse::success($result)` — just the array, no pagination metadata (total, page, total_pages)
- Frontend store detects the array response, falls back to `totalPages=1`, `total=data.length`
- `ProviderDetail.vue` has no pagination controls

**Comparison:** Other list endpoints (Projects, MergeRequests, Providers) all use `PaginatedOutput` correctly. This endpoint is the exception.

**Additional context:**
- `GitProviderInterface::countProjects()` exists and is implemented in both clients — can be used for the total count
- The frontend store already has `remoteProjectsTotalPages`, `remoteProjectsCurrentPage`, `remoteProjectsTotal` refs ready
- The frontend service already defines a `PaginatedRemoteProjects` interface with fallback handling
- GitLab max `per_page`: 100, GitHub max: 100
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Backend: ListRemoteProjectsHandler returns a DTO with PaginatedOutput (items, total, page, per_page, total_pages) using countProjects() for total
- [x] #2 Backend: ListRemoteProjectsController returns pagination metadata in the response (same format as ListProjectsController)
- [x] #3 Frontend: ProviderDetail.vue displays pagination controls for remote projects (previous/next, page indicator)
- [x] #4 Frontend: changing page triggers a new API call with the correct page parameter
- [x] #5 Frontend: imported project badges are preserved across page navigation
- [x] #6 All remote projects are accessible regardless of provider size (100+ repos)
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
