---
id: TASK-071
title: Score de santé global sur DependencyList
status: Done
assignee: []
created_date: '2026-03-18 21:48'
updated_date: '2026-03-18 22:06'
labels:
  - feature
  - dependency
  - DX
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Ajouter un indicateur de santé en haut de la page :\n- % de dépendances à jour (barre de progression)\n- Nombre d'outdated (badge rouge)\n- Nombre de vulnérabilités critiques/high (badge rouge)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Un indicateur global affiche le pourcentage de dépendances à jour
- [x] #2 Le nombre de dépendances outdated est affiché
- [x] #3 Le nombre de vulnérabilités critiques est affiché
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
