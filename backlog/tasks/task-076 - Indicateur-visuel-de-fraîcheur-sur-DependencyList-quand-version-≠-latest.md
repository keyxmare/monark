---
id: TASK-076
title: Indicateur visuel de fraîcheur sur DependencyList quand version ≠ latest
status: Done
assignee: []
created_date: '2026-03-18 21:48'
updated_date: '2026-03-18 22:10'
labels:
  - ui
  - dependency
dependencies: []
priority: low
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Quand `currentVersion` ≠ `latestVersion`, montrer visuellement l'écart au lieu du simple badge « Obsolète ». Par exemple afficher les deux versions côte à côte avec une flèche et une couleur selon l'écart.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 L'écart entre currentVersion et latestVersion est visuellement clair
- [x] #2 Un indicateur coloré montre la sévérité du retard
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
