---
id: TASK-050
title: Badge « Non maintenu » sur la version min dans les cartes provider agrégation
status: Done
assignee: []
created_date: '2026-03-18 20:33'
updated_date: '2026-03-18 20:43'
labels:
  - ui
  - catalog
  - DX
dependencies: []
priority: low
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Les cartes d'agrégation par provider montrent la plage de versions (min → max) mais n'affichent pas de badge « Non maintenu » sur la version min quand elle est EOL. Intégrer le composable `useFrameworkLts` pour afficher le badge.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Un badge Non maintenu s'affiche à côté de la version min si elle est EOL
- [x] #2 Le badge utilise le même style que dans le tableau
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
