---
id: TASK-086
title: >-
  DependencyList — ne plus afficher les cartes d'agrégation par projet en header
  (trop volumineux)
status: Done
assignee: []
created_date: '2026-03-18 22:22'
updated_date: '2026-03-18 22:24'
labels:
  - UX
  - dependency
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Les cartes d'agrégation par projet en haut de la page DependencyList prennent trop de place quand il y a beaucoup de projets. Les remplacer par un résumé plus compact ou les masquer par défaut avec un toggle.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Les cartes d'agrégation ne prennent plus autant de place en header
- [x] #2 L'information reste accessible (collapse, toggle, ou résumé compact)
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
