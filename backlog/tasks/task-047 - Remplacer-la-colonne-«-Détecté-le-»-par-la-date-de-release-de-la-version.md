---
id: TASK-047
title: Remplacer la colonne « Détecté le » par la date de release de la version
status: Done
assignee: []
created_date: '2026-03-18 20:33'
updated_date: '2026-03-18 20:41'
labels:
  - UX
  - catalog
dependencies: []
priority: low
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
La colonne « Détecté le » affiche la date du scan, peu utile. La remplacer par la date de release de la version installée (disponible via endoflife.date) pour apporter une information plus pertinente sur l'ancienneté de la version.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 La colonne affiche la date de release de la version du framework
- [x] #2 Si la date n'est pas disponible, afficher « — »
- [x] #3 Le changement s'applique sur la page Stacks techniques et le détail projet
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
