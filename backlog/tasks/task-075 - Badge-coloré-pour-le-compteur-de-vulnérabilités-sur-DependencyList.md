---
id: TASK-075
title: Badge coloré pour le compteur de vulnérabilités sur DependencyList
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
Le compteur de vulnérabilités est affiché en chiffre brut. Le rendre visuel avec un badge coloré :\n- 0 = gris\n- 1-3 = orange\n- 4+ = rouge
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le compteur de vulnérabilités utilise un badge coloré
- [x] #2 Les couleurs reflètent la criticité (0=gris, 1-3=orange, 4+=rouge)
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
