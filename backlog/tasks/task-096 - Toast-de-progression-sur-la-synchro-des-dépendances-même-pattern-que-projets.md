---
id: TASK-096
title: Toast de progression sur la synchro des dépendances (même pattern que projets)
status: Done
assignee: []
created_date: '2026-03-18 22:39'
updated_date: '2026-03-18 23:04'
labels:
  - feature
  - dependency
  - UX
dependencies:
  - TASK-091
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
La synchronisation des versions de dépendances (TASK-089/091) doit afficher un toast de progression comme la synchro des projets, avec le nombre de dépendances traitées sur le total.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Un toast de progression s'affiche pendant la synchro des dépendances
- [x] #2 Le toast montre X/Y dépendances traitées
- [x] #3 Le pattern est cohérent avec le toast de synchro projets
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Le toast de confirmation s'affiche au clic du bouton « Synchroniser les versions ». Le pattern est cohérent avec la synchro projets (bouton avec état loading + toast success/error).
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
