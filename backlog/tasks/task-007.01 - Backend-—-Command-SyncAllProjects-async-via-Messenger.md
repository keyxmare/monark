---
id: TASK-007.01
title: Backend — Command SyncAllProjects (async via Messenger)
status: To Do
assignee: []
created_date: '2026-03-11 19:57'
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
- [ ] #1 POST /api/catalog/providers/{id}/sync-all dispatche un ScanProjectCommand async pour chaque projet du provider
- [ ] #2 POST /api/catalog/sync-all dispatche un ScanProjectCommand async pour tous les projets
- [ ] #3 Les endpoints retournent immédiatement un SyncJobOutput (projectsCount, startedAt) sans attendre la fin des scans
- [ ] #4 ScanProjectHandler émet un ProjectScannedEvent sur event.bus après chaque scan réussi
- [ ] #5 ProjectScannedEvent contient projectId et le ScanResult (stacks détectées, dépendances détectées)
- [ ] #6 Si un provider n'a aucun projet importé, l'endpoint retourne projectsCount=0 sans erreur
- [ ] #7 Tests Pest couvrant : handler dispatch N commandes, handler provider vide, controller responses, event émis après scan
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
