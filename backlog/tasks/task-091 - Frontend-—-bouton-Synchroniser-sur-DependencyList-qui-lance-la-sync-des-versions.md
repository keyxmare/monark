---
id: TASK-091
title: >-
  Frontend — bouton Synchroniser sur DependencyList qui lance la sync des
  versions
status: Done
assignee: []
created_date: '2026-03-18 22:36'
updated_date: '2026-03-18 23:04'
labels:
  - feature
  - dependency
  - UX
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Le bouton « Tout synchroniser » sur la page Dépendances redirige actuellement vers les Fournisseurs. Le remplacer par un vrai bouton qui appelle `POST /api/dependency/sync` pour synchroniser les versions depuis les registres.

## Comportement
- Bouton « Synchroniser les versions » avec état loading
- Appel API → réponse 202 (async)
- Toast de confirmation
- Après sync, les colonnes latestVersion et isOutdated sont mises à jour
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le bouton lance la sync des versions au lieu de rediriger
- [x] #2 Un toast confirme le lancement
- [x] #3 Les données se mettent à jour après la sync
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Analyse de code statique doit passer
- [ ] #6 Deptrac doit passer
<!-- DOD:END -->
