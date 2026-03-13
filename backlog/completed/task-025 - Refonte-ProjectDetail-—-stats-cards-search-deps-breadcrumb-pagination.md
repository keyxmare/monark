---
id: TASK-025
title: 'Refonte ProjectDetail — stats cards, search deps, breadcrumb, pagination'
status: Done
assignee: []
created_date: '2026-03-13 12:08'
updated_date: '2026-03-13 12:44'
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
- [x] #1 Des stats cards affichent visibility, stacks count, pipelines count, scan freshness
- [x] #2 Un breadcrumb remplace le back link
- [x] #3 L'onglet dépendances a un champ de recherche et un filtre par package manager
- [x] #4 Les tabs supportent la pagination
- [x] #5 La suppression d'un tech stack passe par un ConfirmDialog
- [x] #6 Un toast s'affiche après un scan réussi
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Résumé

Refonte complète de `ProjectDetail.vue` :
- **Breadcrumb** : `Projects / [Nom]` remplace le back link
- **Stats cards** : 4 cartes (visibility, tech stacks count, merge requests count, scan freshness avec code couleur)
- **Search + filter deps** : champ de recherche + select par package manager (composer/npm/pip)
- **Pagination** : sur les 3 onglets (tech stacks, dépendances, merge requests) à 20 items/page
- **ConfirmDialog** : confirmation avant suppression de tech stack
- **Toast** : notification success après scan réussi
- **Truncate URLs** : URLs longues tronquées avec tooltip

## Fichiers modifiés
- `frontend/src/catalog/pages/ProjectDetail.vue` — réécriture complète
- `frontend/src/shared/i18n/locales/en.json` — ajout clés (searchDependencies, allPackageManagers, noMatchingDependencies, confirmDeleteStack*, lastScan, freshness.*)
- `frontend/src/shared/i18n/locales/fr.json` — idem FR

## Tests
- 24/24 fichiers, 138/138 tests ✅
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
