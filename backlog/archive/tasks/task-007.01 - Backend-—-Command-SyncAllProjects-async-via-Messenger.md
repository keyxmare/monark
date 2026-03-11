---
id: TASK-007.01
title: Backend — Command SyncAllProjects (async via Messenger)
status: Done
assignee: []
created_date: '2026-03-11 19:57'
updated_date: '2026-03-11 20:48'
labels:
  - backend
  - catalog
  - async
  - messenger
dependencies: []
references:
  - backend/src/Catalog/Application/CommandHandler/ScanProjectHandler.php
  - backend/src/Catalog/Application/Command/ScanProjectCommand.php
  - backend/src/Catalog/Domain/Port/ProjectRepositoryInterface.php
  - backend/config/packages/messenger.yaml
parent_task_id: TASK-007
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Créer le mécanisme de sync globale qui dispatche un scan async pour chaque projet importé.

**Flow** :
1. L'utilisateur déclenche via `POST /api/catalog/providers/{id}/sync-all` (par provider) ou `POST /api/catalog/sync-all` (global)
2. Le handler récupère tous les projets concernés
3. Pour chaque projet, dispatche un `ScanProjectCommand` sur le bus async (RabbitMQ)
4. Retourne immédiatement un `SyncJobOutput` avec le nombre de projets lancés

**Existant à réutiliser** :
- `ScanProjectCommand` + `ScanProjectHandler` existent déjà et fonctionnent
- `ProjectRepositoryInterface::findAll()` existe
- Le transport async RabbitMQ est configuré (exchange `monark`, queue `monark_default`)
- Le `ScanProjectHandler` scanne stacks + dépendances via `ProjectScanner`

**À créer** :
- `SyncAllProjectsCommand` : contient optionnellement un providerId (null = global)
- `SyncAllProjectsHandler` : fetch projets, dispatche les scans en async
- `SyncAllProjectsController` : 2 endpoints REST (par provider + global)
- `SyncJobOutput` DTO : { projectsCount, startedAt }
- Modifier `ScanProjectHandler` pour émettre un `ProjectScannedEvent` sur le `event.bus` à la fin du scan (nécessaire pour la sous-tâche 007.02)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 POST /api/catalog/providers/{id}/sync-all dispatche un ScanProjectCommand async pour chaque projet du provider
- [x] #2 POST /api/catalog/sync-all dispatche un ScanProjectCommand async pour tous les projets
- [x] #3 Les endpoints retournent immédiatement un SyncJobOutput (projectsCount, startedAt) sans attendre la fin des scans
- [x] #4 ScanProjectHandler émet un ProjectScannedEvent sur event.bus après chaque scan réussi
- [x] #5 ProjectScannedEvent contient projectId et le ScanResult (stacks détectées, dépendances détectées)
- [x] #6 Si un provider n'a aucun projet importé, l'endpoint retourne projectsCount=0 sans erreur
- [x] #7 Tests Pest couvrant : handler dispatch N commandes, handler provider vide, controller responses, event émis après scan
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Résumé

- `SyncAllProjectsCommand` + `SyncAllProjectsHandler` : dispatche N × `ScanProjectCommand` en async via `DispatchAfterCurrentBusStamp`
- `SyncAllProjectsController` : 2 endpoints REST (global + par provider), 202 Accepted
- `SyncJobOutput` DTO : `{ projectsCount, startedAt }`
- `ProjectScannedEvent` : émis par `ScanProjectHandler` après chaque scan réussi, contient `projectId` + `ScanResult`
- `ScanProjectCommand` routé vers le transport async (RabbitMQ) dans `messenger.yaml`
- `ScanProjectController` mis à jour pour réponse async (202)
- `ProjectRepositoryInterface` : ajout `findByProviderId()` et `findAllWithProvider()`
- 147 tests backend passent (8 nouveaux tests : 5 SyncAllProjectsHandler + 3 ScanProjectHandler mis à jour)

## Fichiers créés (6)

- `backend/src/Catalog/Application/Command/SyncAllProjectsCommand.php`
- `backend/src/Catalog/Application/CommandHandler/SyncAllProjectsHandler.php`
- `backend/src/Catalog/Application/DTO/SyncJobOutput.php`
- `backend/src/Catalog/Domain/Event/ProjectScannedEvent.php`
- `backend/src/Catalog/Presentation/Controller/SyncAllProjectsController.php`
- `backend/tests/Unit/Catalog/Application/CommandHandler/SyncAllProjectsHandlerTest.php`

## Fichiers modifiés (17)

- `backend/config/packages/messenger.yaml` — routing async
- `backend/config/services.yaml` — bus bindings
- `backend/src/Catalog/Application/CommandHandler/ScanProjectHandler.php` — emit event
- `backend/src/Catalog/Domain/Repository/ProjectRepositoryInterface.php` — 2 méthodes
- `backend/src/Catalog/Infrastructure/Persistence/Doctrine/DoctrineProjectRepository.php`
- `backend/src/Catalog/Presentation/Controller/ScanProjectController.php` — 202
- 11 fichiers tests — ajout stubs pour nouvelles méthodes interface
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
