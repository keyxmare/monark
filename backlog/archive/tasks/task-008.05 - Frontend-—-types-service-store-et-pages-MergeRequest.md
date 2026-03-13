---
id: TASK-008.05
title: 'Frontend — types, service, store et pages MergeRequest'
status: Done
assignee: []
created_date: '2026-03-12 08:04'
updated_date: '2026-03-12 08:28'
labels:
  - frontend
  - catalog
  - vue
dependencies:
  - TASK-008.04
parent_task_id: TASK-008
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Implémenter le module frontend complet pour les MergeRequests dans le context Catalog.

## Types

- `MergeRequest`, `MergeRequestStatus`, `MergeRequestFilters` dans `catalog/types/merge-request.ts`

## Service

- `merge-request.service.ts` : list (avec filtres + projectId), get par id

## Store Pinia

- `merge-request.ts` : fetchAll, fetchOne, état loading/error/pagination

## Pages & composants

- `MergeRequestList.vue` — table avec badges status, auteur, dates, filtres
- Tab "Merge Requests" dans `ProjectDetail.vue` avec compteur

## i18n

- Clés EN/FR pour labels, statuts, filtres, empty state

## Tests

- Tests unitaires store (Vitest)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Types TypeScript MergeRequest avec tous les champs et enums
- [x] #2 Service API avec list (filtres, pagination) et get
- [x] #3 Store Pinia avec fetchAll, fetchOne, loading/error
- [x] #4 Page MergeRequestList avec table, badges status, filtres
- [x] #5 Tab MR dans ProjectDetail avec compteur dynamique
- [x] #6 Traductions EN/FR complètes
- [x] #7 Tests unitaires store Vitest
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## TASK-008.05 — Frontend MergeRequest

### Fichiers créés
- `frontend/src/catalog/types/merge-request.ts` — Types TS (MergeRequestStatus, MergeRequest)
- `frontend/src/catalog/services/merge-request.service.ts` — Service API (list avec filtres, get)
- `frontend/src/catalog/stores/merge-request.ts` — Store Pinia (fetchAll, fetchOne, loading/error)
- `frontend/src/catalog/pages/MergeRequestList.vue` — Page standalone avec filtres status/author, badges, liens externes
- `frontend/tests/unit/catalog/stores/merge-request.test.ts` — 4 tests Vitest

### Fichiers modifiés
- `frontend/src/catalog/pages/ProjectDetail.vue` — Tab "Merge Requests" avec compteur dynamique et table MR
- `frontend/src/catalog/routes.ts` — Route `/catalog/projects/:projectId/merge-requests`
- `frontend/src/shared/i18n/locales/en.json` — Traductions EN (catalog.mergeRequests.*)
- `frontend/src/shared/i18n/locales/fr.json` — Traductions FR

### Tests
- 114 tests Vitest (21 fichiers) — tous green
- Backend 215 tests Pest — tous green

### Note
- ProjectDetail.vue atteint 560 lignes (seuil 450). Extraction des panels en composants à planifier.
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [x] #1 Les tests doivent passer
- [x] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
