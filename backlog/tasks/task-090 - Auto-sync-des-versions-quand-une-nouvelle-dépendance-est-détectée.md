---
id: TASK-090
title: Auto-sync des versions quand une nouvelle dépendance est détectée
status: Done
assignee: []
created_date: '2026-03-18 22:36'
updated_date: '2026-03-18 23:33'
labels:
  - feature
  - dependency
  - event
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Quand le scanner détecte une nouvelle dépendance (pas encore connue en base), déclencher automatiquement une synchronisation de ses versions depuis le registre.

## Implémentation
- Event listener sur `ProjectScannedEvent`
- Vérifier quelles dépendances du scan n'ont pas encore de `DependencyVersion` en base
- Dispatcher un `SyncDependencyVersionsCommand` pour celles-ci
- Idem quand un nouveau projet est importé (ses dépendances sont nouvelles)

## Optimisation
- Ne synchroniser que les dépendances qui n'ont pas encore été sync (pas de doublon)
- Regrouper les appels registre par batch
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Les nouvelles dépendances détectées déclenchent une sync auto
- [x] #2 Les dépendances déjà connues ne sont pas re-syncées
- [x] #3 L'import d'un nouveau projet déclenche la sync de ses dépendances
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Event listener `SyncNewDependencyVersionsListener` sur `ProjectScannedEvent` :\n- Vérifie chaque dépendance du scan — si aucune `DependencyVersion` n'existe en base, c'est nouveau\n- Collecte les noms des packages inconnus\n- Dispatch un `SyncDependencyVersionsCommand` ciblé uniquement sur ces packages\n- Les dépendances déjà connues ne sont pas re-syncées\n- Fonctionne aussi à l'import d'un nouveau projet (le scan est lancé → l'event est dispatché)
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
