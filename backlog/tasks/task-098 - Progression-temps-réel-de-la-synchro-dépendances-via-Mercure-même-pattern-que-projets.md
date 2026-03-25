---
id: TASK-098
title: >-
  Progression temps réel de la synchro dépendances via Mercure (même pattern que
  projets)
status: Done
assignee: []
created_date: '2026-03-18 23:08'
updated_date: '2026-03-18 23:13'
labels:
  - feature
  - dependency
  - UX
  - mercure
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
La synchro des versions de dépendances est async (RabbitMQ) mais n'offre aucun feedback de progression. Implémenter le même pattern que la synchro projets avec Mercure/SSE.

## Pattern de référence (projets)
- Le `SyncAllProjectsHandler` crée un `SyncJob` avec le nombre de projets
- Chaque handler dispatche `ProjectSyncCompletedEvent` quand il termine
- Un listener Mercure publie sur un topic SSE
- Le frontend `useSyncProgress` écoute le topic et met à jour le toast X/Y

## Implémentation dépendances
1. **Backend** : le `SyncDependencyVersionsHandler` doit dispatcher un event après chaque package synchronisé (ex: `DependencyVersionSyncedEvent` avec packageName + index + total)
2. **Backend** : un listener Mercure publie sur un topic `/dependency/sync/{syncId}`
3. **Frontend** : composable `useDependencySyncProgress` qui écoute le topic Mercure
4. **Frontend** : toast de progression X/Y packages synchronisés
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Un toast de progression s'affiche avec X/Y packages traités
- [x] #2 La progression est en temps réel via Mercure/SSE
- [x] #3 Le pattern est cohérent avec la synchro projets
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
**Backend** :
- Le handler publie sur Mercure (`/dependency/sync/{syncId}`) après chaque package synchronisé
- Payload : `{ syncId, completed, total, status, lastPackage }`
- Le contrôleur génère un `syncId` UUID et le retourne au frontend (202)

**Frontend** :
- Composable `useDependencySyncProgress` — écoute le topic Mercure via SSE
- Toast avec progression X/Y + nom du dernier package traité
- Transition vers success quand status = completed
- Pattern identique à `useSyncProgress` des projets
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
