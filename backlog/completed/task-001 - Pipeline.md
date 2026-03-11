---
id: TASK-001
title: Pipeline
status: Done
assignee: []
created_date: '2026-03-11 15:30'
updated_date: '2026-03-11 16:39'
labels: []
dependencies: []
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Dans l'onglet Projects > Pipelines.

Je veux voir les dernières pipelines (10 max) sur la branche principale ainsi que leur status
<!-- SECTION:DESCRIPTION:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation à jour
<!-- DOD:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Résumé

Ajout du filtre par branche (`ref`) sur le listing des pipelines. Dans la page ProjectDetail, le tab Pipelines affiche désormais les 10 dernières pipelines de la branche principale (`defaultBranch`) du projet.

## Changements

### Backend
- `PipelineRepositoryInterface` : ajout param `?string $ref` sur `findByProjectId()` et `countByProjectId()`
- `DoctrinePipelineRepository` : filtrage conditionnel par `ref` dans les QueryBuilders
- `ListPipelinesQuery` : ajout propriété `?string $ref`
- `ListPipelinesHandler` : propagation du filtre `ref` au repository
- `ListPipelinesController` : lecture du query param `ref`

### Frontend
- `pipeline.service.ts` : ajout param `ref` sur `list()`
- Pipeline store : ajout param `ref` sur `fetchAll()`
- `ProjectDetail.vue` : appel `fetchAll(1, 10, projectId, defaultBranch)`

### Tests
- Backend : 136 tests, 372 assertions, 0 failures (nouveau test `passes ref filter to repository`)
- Frontend : 91 tests, 0 failures (nouveau test `fetches pipelines filtered by ref`)

### Documentation
- OpenAPI : ajout endpoints `/catalog/pipelines` (GET list + query param `ref`) et `/catalog/pipelines/{id}` (GET detail) + schemas Pipeline
<!-- SECTION:FINAL_SUMMARY:END -->
