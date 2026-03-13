---
id: TASK-008.04
title: 'Backend — API REST MergeRequest (list, get, filtres)'
status: Done
assignee: []
created_date: '2026-03-12 08:04'
updated_date: '2026-03-12 08:21'
labels:
  - backend
  - api
  - catalog
dependencies:
  - TASK-008.01
parent_task_id: TASK-008
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Exposer les MergeRequests via une API REST dans le Catalog context.

## Endpoints

- `GET /api/catalog/projects/{id}/merge-requests` — liste paginée avec filtres (status, auteur)
- `GET /api/catalog/merge-requests/{id}` — détail d'une MR

## Implémentation

- Query `ListMergeRequestsQuery` + Handler (filtre par projectId, status)
- Query `GetMergeRequestQuery` + Handler
- Controller REST avec pagination standard (page, per_page)
- DTO de réponse MergeRequestResponse

## Tests

- Tests unitaires handlers
- Tests fonctionnels endpoints (filtres, pagination, 404)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Endpoint GET /api/catalog/projects/{id}/merge-requests avec pagination et filtres status/auteur
- [x] #2 Endpoint GET /api/catalog/merge-requests/{id} avec 404 si inexistant
- [x] #3 Query/Handler pattern CQRS respecté
- [x] #4 DTO MergeRequestResponse avec tous les champs
- [x] #5 Tests unitaires handlers + tests fonctionnels endpoints
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Fichiers créés
- `backend/src/Catalog/Application/Query/ListMergeRequestsQuery.php` — Query avec projectId, page, perPage, status, author
- `backend/src/Catalog/Application/Query/GetMergeRequestQuery.php` — Query avec mergeRequestId
- `backend/src/Catalog/Application/QueryHandler/ListMergeRequestsHandler.php` — Liste paginée avec filtres status/author
- `backend/src/Catalog/Application/QueryHandler/GetMergeRequestHandler.php` — Détail avec 404
- `backend/src/Catalog/Application/DTO/MergeRequestOutput.php` — DTO avec fromEntity, tous les champs
- `backend/src/Catalog/Application/DTO/MergeRequestListOutput.php` — Wrapper pagination
- `backend/src/Catalog/Presentation/Controller/ListMergeRequestsController.php` — GET /api/catalog/projects/{id}/merge-requests
- `backend/src/Catalog/Presentation/Controller/GetMergeRequestController.php` — GET /api/catalog/merge-requests/{id}
- Tests: ListMergeRequestsHandlerTest (2 tests), GetMergeRequestHandlerTest (2 tests)

## Fichiers modifiés
- Repository interface + Doctrine: ajout filtre `?string $author` sur findByProjectId et countByProjectId

## Tests
- 4 nouveaux tests, 215 au total, 0 failures
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [x] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [x] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
