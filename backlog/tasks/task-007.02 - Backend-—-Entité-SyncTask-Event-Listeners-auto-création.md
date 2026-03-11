---
id: TASK-007.02
title: Backend — Entité SyncTask + Event Listeners auto-création
status: To Do
assignee: []
created_date: '2026-03-11 19:57'
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
- [ ] #1 L'entité SyncTask est persistée en base avec tous les champs décrits (type, severity, title, description, status, metadata, projectId)
- [ ] #2 Le listener crée une SyncTask type=outdated_dependency pour chaque dépendance où isOutdated=true après un ProjectScannedEvent
- [ ] #3 Le listener crée une SyncTask type=vulnerability pour chaque vulnérabilité détectée, avec la severity correspondante
- [ ] #4 Le dédoublonnage empêche la création de doublons : si une SyncTask ouverte existe pour le même (projectId, type, clé metadata), elle est mise à jour au lieu d'être recréée
- [ ] #5 GET /api/activity/sync-tasks retourne la liste filtrée (par status, type, severity, projectId)
- [ ] #6 PATCH /api/activity/sync-tasks/{id} permet de changer le status (open → acknowledged → resolved/dismissed)
- [ ] #7 GET /api/activity/sync-tasks/stats retourne les compteurs agrégés par type, severity et status
- [ ] #8 Tests Pest : listeners créent les bonnes tâches, dédoublonnage fonctionne, CRUD endpoints, stats aggregation
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
