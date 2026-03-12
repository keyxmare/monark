---
id: TASK-018.02
title: >-
  PHPStan — Corriger les 83 erreurs des contexts Activity, Identity, Assessment,
  Dependency
status: Done
assignee: []
created_date: '2026-03-12 17:47'
updated_date: '2026-03-12 18:46'
labels:
  - phpstan
  - backend
dependencies: []
parent_task_id: TASK-018
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Répartition :
- Activity : 46 erreurs (RabbitMqMonitor 11, DoctrineSyncTaskRepo 9, SyncTask 7, EventListeners 2-3 chacun)
- Identity : 16 erreurs (User 4, AccessToken 3, controllers/repos)
- Assessment : 14 erreurs
- Dependency : 7 erreurs

Principalement des types Doctrine (mixed), des propriétés sur entités, et des constructeurs incomplets.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 PHPStan 0 erreurs sur src/Activity/, src/Identity/, src/Assessment/, src/Dependency/
- [x] #2 Aucune régression
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Résolu les 83 erreurs PHPStan sur Activity (46), Identity (16), Assessment (14), Dependency (7) :

- Entités : `@param` pour types array (roles, scopes, metadata, payload)
- Doctrine repos : `@var list<Entity>` sur getResult()
- Controllers : import + `@var` pour DTOs de résultat
- RabbitMqMonitor : extraction type-safe des réponses API RabbitMQ
- SyncTask : accès metadata avec is_string checks
- User : assertion non-empty-string pour getUserIdentifier

PHPStan 0 erreurs sur tout le projet (level max). Commit: `c614a0b`
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [x] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [x] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
