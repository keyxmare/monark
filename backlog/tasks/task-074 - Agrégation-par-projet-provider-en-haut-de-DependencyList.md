---
id: TASK-074
title: Agrégation par projet/provider en haut de DependencyList
status: Done
assignee: []
created_date: '2026-03-18 21:48'
updated_date: '2026-03-18 22:10'
labels:
  - feature
  - dependency
  - DX
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Cartes d'agrégation en haut de la page montrant par projet ou provider :\n- Nombre de dépendances total\n- Nombre d'outdated\n- Nombre de vulnérabilités\n\nMême pattern que les cartes provider sur la page Stacks techniques.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Des cartes d'agrégation par projet s'affichent en haut de page
- [x] #2 Chaque carte montre le nombre de deps total, outdated et vulnérabilités
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
