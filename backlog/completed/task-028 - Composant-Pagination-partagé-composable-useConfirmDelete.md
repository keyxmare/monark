---
id: TASK-028
title: Composant Pagination partagé + composable useConfirmDelete
status: Done
assignee: []
created_date: '2026-03-13 13:16'
updated_date: '2026-03-13 14:38'
labels:
  - frontend
  - refacto
  - dx
dependencies: []
references:
  - frontend/src/catalog/pages/ProjectList.vue
  - frontend/src/catalog/pages/ProjectDetail.vue
  - frontend/src/catalog/pages/ProviderList.vue
  - frontend/src/catalog/pages/ProviderDetail.vue
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Extraire la logique dupliquée de pagination et de confirmation de suppression dans des éléments partagés réutilisables.

**Pagination** : chaque page (ProjectList, ProjectDetail x3 tabs, ProviderList, ProviderDetail remote projects) réimplémente le même pattern prev/next/currentPage. Créer un composant `<Pagination>` dans `shared/components/` et un composable `usePagination()`.

**useConfirmDelete** : le pattern `deleteTarget` ref + ConfirmDialog est dupliqué dans ProjectList, ProjectDetail (tech stacks), ProviderList. Extraire dans un composable `useConfirmDelete()`.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Composant <Pagination> partagé avec props page/totalPages/total et emit update:page
- [x] #2 Composable usePagination(fetchFn) avec gestion page/totalPages
- [x] #3 Composable useConfirmDelete() avec target ref, open/confirm/cancel et ConfirmDialog intégré
- [x] #4 Toutes les pages existantes migrées vers ces éléments partagés
- [x] #5 Les tests frontend passent
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Composant `<Pagination>` et composable `useConfirmDelete()` créés dans shared/, migrés sur ProjectList, ProjectDetail (3 tabs), ProviderList et ProviderDetail. 138 tests passent, type-check OK.
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Analyse de code statique doit passer
- [ ] #6 Deptrac doit passer
<!-- DOD:END -->
