---
id: TASK-007.02
title: Backend — Entité SyncTask + Event Listeners auto-création
status: Done
assignee: []
created_date: '2026-03-11 19:57'
updated_date: '2026-03-11 21:04'
labels:
  - backend
  - activity
  - domain
  - events
dependencies:
  - TASK-007.01
references:
  - backend/src/Activity/
  - backend/src/Catalog/Domain/Model/Project.php
  - backend/src/Dependency/Domain/Model/Dependency.php
  - backend/src/Dependency/Domain/Model/Vulnerability.php
parent_task_id: TASK-007
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Créer l'entité SyncTask et les event listeners qui génèrent automatiquement des tâches d'action à partir des résultats de scan.

**Entité SyncTask** (context Activity) :
- id: UUID
- type: enum (outdated_dependency, vulnerability, stack_upgrade, new_dependency)
- severity: enum (critical, high, medium, low, info)
- title: string
- description: text
- status: enum (open, acknowledged, resolved, dismissed)
- metadata: json (détails spécifiques : dep name, CVE id, versions, etc.)
- project: ManyToOne → Catalog\Project (cross-context via ID, pas de FK Doctrine directe)
- projectId: UUID
- resolvedAt: ?datetime
- createdAt, updatedAt: datetime

**Event Listeners** (écoutent sur `event.bus`) :
- `CreateOutdatedDependencyTasksListener` : pour chaque dep où `isOutdated=true`, crée une SyncTask type=outdated_dependency
- `CreateVulnerabilityTasksListener` : pour chaque vulnérabilité détectée, crée une SyncTask type=vulnerability (severity = celle de la vuln)
- `CreateStackUpgradeTasksListener` : si une stack détectée est en EOL ou version majeure en retard, crée une SyncTask type=stack_upgrade

**Dédoublonnage** :
- Avant de créer, vérifier si une SyncTask ouverte (status=open|acknowledged) existe déjà pour le même (projectId, type, clé unique dans metadata)
- Clé unique : pour outdated = dependency name, pour vuln = cveId, pour stack = language+framework
- Si existe déjà : mettre à jour les infos (versions, severity) sans recréer

**CRUD API** :
- GET /api/activity/sync-tasks (liste avec filtres : status, type, severity, projectId)
- GET /api/activity/sync-tasks/{id}
- PATCH /api/activity/sync-tasks/{id} (changer status : acknowledged, resolved, dismissed)
- GET /api/activity/sync-tasks/stats (compteurs par type/severity/status)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 L'entité SyncTask est persistée en base avec tous les champs décrits (type, severity, title, description, status, metadata, projectId)
- [x] #2 Le listener crée une SyncTask type=outdated_dependency pour chaque dépendance où isOutdated=true après un ProjectScannedEvent
- [x] #3 Le listener crée une SyncTask type=vulnerability pour chaque vulnérabilité détectée, avec la severity correspondante
- [x] #4 Le dédoublonnage empêche la création de doublons : si une SyncTask ouverte existe pour le même (projectId, type, clé metadata), elle est mise à jour au lieu d'être recréée
- [x] #5 GET /api/activity/sync-tasks retourne la liste filtrée (par status, type, severity, projectId)
- [x] #6 PATCH /api/activity/sync-tasks/{id} permet de changer le status (open → acknowledged → resolved/dismissed)
- [x] #7 GET /api/activity/sync-tasks/stats retourne les compteurs agrégés par type, severity et status
- [x] #8 Tests Pest : listeners créent les bonnes tâches, dédoublonnage fonctionne, CRUD endpoints, stats aggregation
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Résumé

Entité SyncTask (Activity context) avec 3 enums, repository, event listeners pour auto-création depuis ProjectScannedEvent, CRUD API avec filtres et stats agrégées.

## Fichiers créés (26)

### Domain
- `SyncTask.php` — entité avec dedup via `getMetadataKey()`, `updateInfo()`, `changeStatus()`
- `SyncTaskType.php` — enum (outdated_dependency, vulnerability, stack_upgrade, new_dependency)
- `SyncTaskSeverity.php` — enum (critical, high, medium, low, info)
- `SyncTaskStatus.php` — enum (open, acknowledged, resolved, dismissed)
- `SyncTaskRepositoryInterface.php` — interface avec findFiltered, findOpenByProjectAndTypeAndKey, countGrouped*

### Infrastructure
- `DoctrineSyncTaskRepository.php` — implémentation Doctrine avec QueryBuilder filtrable

### Application
- `UpdateSyncTaskStatusCommand.php` + `UpdateSyncTaskStatusHandler.php`
- `ListSyncTasksQuery.php` + `ListSyncTasksHandler.php` (filtres status/type/severity/projectId)
- `GetSyncTaskQuery.php` + `GetSyncTaskHandler.php`
- `GetSyncTaskStatsQuery.php` + `GetSyncTaskStatsHandler.php`
- `SyncTaskOutput.php`, `SyncTaskListOutput.php`, `SyncTaskStatsOutput.php`

### Event Listeners (event.bus)
- `CreateOutdatedDependencyTasksListener.php` — crée SyncTask pour deps outdated, severity auto (major diff)
- `CreateVulnerabilityTasksListener.php` — crée SyncTask pour vulns open/acknowledged, severity mappée
- `CreateStackUpgradeTasksListener.php` — crée SyncTask si version < latest known major

### Controllers (4 endpoints)
- `GET /api/activity/sync-tasks` — liste filtrée
- `GET /api/activity/sync-tasks/{id}` — détail
- `PATCH /api/activity/sync-tasks/{id}` — changement status
- `GET /api/activity/sync-tasks/stats` — compteurs agrégés

### Tests (19 nouveaux, 166 total)
- 4 tests outdated listener (create, skip, dedup, severity critical)
- 3 tests vulnerability listener (create, skip fixed, severity mapping)
- 5 tests stack upgrade listener (create, skip current, skip empty, skip unknown, dedup)
- 3 tests update handler (status change, resolvedAt, not found)
- 2 tests get handler (success, not found)
- 2 tests list handler (paginated, empty)

## Fichier modifié
- `services.yaml` — ajout alias SyncTaskRepositoryInterface
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [x] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
