---
id: TASK-054
title: Score de santé global en haut de la page Stacks techniques
status: Done
assignee: []
created_date: '2026-03-18 20:33'
updated_date: '2026-03-18 20:47'
labels:
  - feature
  - catalog
  - DX
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Ajouter un indicateur de santé global en haut de la page montrant :
- **% de stacks à jour** (barre de progression verte)
- **X stacks non maintenues** (badge rouge)
- **Y stacks inactives** (badge orange)

Donne une vision instantanée de l'état de santé technique du parc de projets.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Un indicateur global affiche le pourcentage de stacks à jour
- [x] #2 Le nombre de stacks Non maintenu et Inactif est affiché
- [x] #3 L'indicateur est visuellement clair (barre de progression ou jauge)
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
