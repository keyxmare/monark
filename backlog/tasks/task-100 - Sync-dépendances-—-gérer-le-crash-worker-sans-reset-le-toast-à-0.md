---
id: TASK-100
title: Sync dépendances — gérer le crash worker sans reset le toast à 0
status: Done
assignee: []
created_date: '2026-03-18 23:22'
updated_date: '2026-03-18 23:29'
labels:
  - bug
  - dependency
  - UX
  - mercure
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Quand le worker crash (OOM, erreur) pendant la sync, le toast de progression se reset à 0 au lieu d'afficher un état d'erreur. Le frontend perd le contexte car la connexion Mercure ne reçoit plus de messages.

## Fix
- Le composable `useDependencySyncProgress` doit gérer le timeout : si aucun message Mercure n'arrive pendant X secondes, afficher un warning "Sync interrompue"
- Si la connexion SSE se ferme, le toast doit passer en erreur au lieu de disparaître
- Ajouter un fallback polling sur un endpoint de statut si Mercure est perdu
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 En cas de crash worker, le toast affiche un état d'erreur
- [x] #2 Le toast ne se reset pas à 0 après un crash
- [x] #3 Un timeout détecte l'absence de progression
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Le composable `useDependencySyncProgress` a maintenant un timeout de 30s. Si aucun message Mercure n'arrive pendant 30s (crash worker, OOM, etc.), le toast passe en erreur au lieu de rester bloqué ou se reset. Chaque message reçu reset le timer.
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
