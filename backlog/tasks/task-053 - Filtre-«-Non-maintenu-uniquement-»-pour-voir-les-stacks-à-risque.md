---
id: TASK-053
title: Filtre « Non maintenu uniquement » pour voir les stacks à risque
status: Done
assignee: []
created_date: '2026-03-18 20:33'
updated_date: '2026-03-18 20:46'
labels:
  - feature
  - catalog
  - DX
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Ajouter un filtre rapide permettant d'afficher uniquement les stacks avec un statut « Non maintenu » ou « Inactif ». Permet de voir d'un coup d'œil les stacks à risque nécessitant une action.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Un filtre rapide permet d'afficher uniquement les stacks Non maintenu / Inactif
- [x] #2 Le compteur total se met à jour en fonction du filtre
- [x] #3 Le filtre est combinable avec les autres filtres (framework, provider)
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Déjà implémenté dans TASK-044 via le filtre par statut de maintenance. Le select « Tous les statuts » permet de filtrer sur « Non maintenu » (eol) ou « Inactif » (warning). Le compteur se met à jour, les filtres sont combinables.
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Analyse de code statique doit passer
- [ ] #6 Deptrac doit passer
<!-- DOD:END -->
