---
id: TASK-029
title: 'Search, filter et tri sur ProjectList et ProviderList'
status: Done
assignee: []
created_date: '2026-03-13 13:16'
updated_date: '2026-03-13 14:43'
labels:
  - frontend
  - ux
dependencies:
  - TASK-028
references:
  - frontend/src/catalog/pages/ProjectList.vue
  - frontend/src/catalog/pages/ProviderList.vue
  - frontend/src/catalog/pages/ProjectDetail.vue
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Ajouter des contrôles de recherche, filtrage et tri sur les pages liste pour améliorer la navigation.

**ProjectList** :
- Search par nom de projet
- Filtre par visibilité (all/public/private)
- Tri par nom, date de création

**ProviderList** :
- Search par nom
- Filtre par type (all/gitlab/github/bitbucket)
- Filtre par status (all/connected/pending/error)

S'inspirer du pattern déjà en place sur ProviderDetail (remote projects) et ProjectDetail (dependencies).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 ProjectList: champ recherche par nom avec filtrage client-side
- [x] #2 ProjectList: filtre select par visibilité (all/public/private)
- [x] #3 ProviderList: champ recherche par nom
- [x] #4 ProviderList: filtre select par type (all/gitlab/github/bitbucket)
- [x] #5 ProviderList: filtre select par status (all/connected/pending/error)
- [x] #6 Les filtres se combinent entre eux
- [x] #7 État vide adapté quand aucun résultat ne matche les filtres
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Search/filter ajoutés sur ProjectList (nom + visibilité) et ProviderList (nom + type + status). ProviderCard extrait en composant. 138 tests, type-check OK.
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
