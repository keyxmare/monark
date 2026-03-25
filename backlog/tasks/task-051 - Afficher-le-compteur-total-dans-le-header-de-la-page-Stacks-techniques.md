---
id: TASK-051
title: Afficher le compteur total dans le header de la page Stacks techniques
status: Done
assignee: []
created_date: '2026-03-18 20:33'
updated_date: '2026-03-18 20:44'
labels:
  - ui
  - catalog
  - consistency
dependencies: []
priority: low
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Le header de la page affiche « Stacks techniques » sans compteur total alors que les autres pages montrent le nombre d'éléments. Ajouter le total, ex: « Stacks techniques (42) ».
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le titre affiche le nombre total de stacks entre parenthèses
- [x] #2 Le compteur se met à jour quand des filtres sont appliqués
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
