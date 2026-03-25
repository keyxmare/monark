---
id: TASK-079
title: >-
  Supprimer le bouton « Supprimer » sur les dépendances — elles sont gérées par
  scan
status: Done
assignee: []
created_date: '2026-03-18 21:54'
updated_date: '2026-03-18 22:16'
labels:
  - UX
  - dependency
dependencies: []
priority: low
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Les dépendances sont détectées automatiquement par le scan de projet. Avoir un bouton « Supprimer » n'a pas de sens car la dépendance réapparaîtra au prochain scan. Le bouton delete devrait être retiré de DependencyList et DependencyDetail.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le bouton Supprimer est retiré de DependencyList
- [x] #2 Le bouton Edit est retiré de DependencyList et DependencyDetail
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
