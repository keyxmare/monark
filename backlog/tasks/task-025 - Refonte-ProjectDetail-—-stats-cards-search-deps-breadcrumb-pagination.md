---
id: TASK-025
title: 'Refonte ProjectDetail — stats cards, search deps, breadcrumb, pagination'
status: In Progress
assignee: []
created_date: '2026-03-13 12:08'
updated_date: '2026-03-13 12:41'
labels:
  - frontend
  - catalog
  - ux
  - refacto
dependencies: []
references:
  - frontend/src/catalog/pages/ProjectDetail.vue
  - frontend/src/catalog/pages/ProviderDetail.vue
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
ProjectDetail (560 lignes) manque de stats visuelles, search/filter sur les dépendances, et pagination sur les tabs. Aligner avec le niveau de ProviderDetail.

**Changements :**
1. Stats cards en haut (visibility, tech stacks count, pipelines count, last scan freshness)
2. Breadcrumb (Projects / Name) au lieu du back link
3. Search + filter par package manager sur l'onglet dépendances
4. Pagination sur les tabs (20 items/page au lieu de tout charger)
5. ConfirmDialog avant delete TechStack
6. Toast success après scan
7. Truncate URLs longues
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Des stats cards affichent visibility, stacks count, pipelines count, scan freshness
- [ ] #2 Un breadcrumb remplace le back link
- [ ] #3 L'onglet dépendances a un champ de recherche et un filtre par package manager
- [ ] #4 Les tabs supportent la pagination
- [ ] #5 La suppression d'un tech stack passe par un ConfirmDialog
- [ ] #6 Un toast s'affiche après un scan réussi
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
