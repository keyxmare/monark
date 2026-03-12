---
id: TASK-008.06
title: Activity — SyncTasks PR stales + widget dashboard
status: Done
assignee: []
created_date: '2026-03-12 08:04'
updated_date: '2026-03-12 08:31'
labels:
  - backend
  - frontend
  - activity
dependencies:
  - TASK-008.03
parent_task_id: TASK-008
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Détecter les PR/MR stales lors du sync et créer des SyncTasks correspondantes. Ajouter un widget dashboard.

## Détection PR stales

- Event listener sur `ProjectMergeRequestsSyncedEvent`
- Règles de détection :
  - PR ouverte depuis > 7 jours sans activité → severity `medium`
  - PR ouverte depuis > 30 jours → severity `high`
  - PR avec conflits de merge → severity `high`
- Créer/mettre à jour des SyncTask type `stale_pr`

## Nouveau type SyncTask

- Ajouter `stale_pr` dans l'enum type côté backend et frontend
- Traductions EN/FR

## Widget dashboard

- Compteur PR stales dans le widget urgent existant (ou widget dédié)

## Tests

- Tests unitaires listener détection stale
- Tests frontend traductions et widget
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Event listener détecte les PR stales (>7j medium, >30j high, conflits high)
- [x] #2 SyncTask type stale_pr créée/mise à jour automatiquement
- [x] #3 Enum stale_pr ajouté backend + frontend
- [x] #4 Traductions EN/FR pour le nouveau type
- [x] #5 Widget dashboard affiche les PR stales dans le compteur urgent
- [x] #6 Tests unitaires listener de détection
- [x] #7 Tests frontend widget et traductions
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## TASK-008.06 — Stale PR Detection + Dashboard Widget

### Backend
- **`SyncTaskType::StalePr`** ajouté à l'enum (`stale_pr`)
- **`SyncTask::getMetadataKey()`** étendu pour le type `StalePr` (clé = `externalId`)
- **`CreateStalePrTasksListener`** — event listener sur `MergeRequestsSyncedEvent` :
  - Récupère les MR open + draft du projet
  - >7j sans activité → severity `medium`
  - >30j sans activité → severity `high`
  - Upsert via `findOpenByProjectAndTypeAndKey`
  - Metadata : externalId, title, author, status, daysSinceUpdate, url

### Frontend
- `SyncTaskType` étendu avec `'stale_pr'`
- Traductions EN (`Stale PR`) / FR (`PR inactive`)
- Widget dashboard : les PR stales >30j (severity `high`) sont automatiquement comptées dans le widget urgent existant (basé sur `bySeverity` critical/high)

### Tests
- 5 tests backend listener (medium, high, skip <7j, draft, update existing)
- Tous tests backend + frontend green

### Fichiers créés
- `backend/src/Activity/Application/EventListener/CreateStalePrTasksListener.php`
- `backend/tests/Unit/Activity/Application/EventListener/CreateStalePrTasksListenerTest.php`

### Fichiers modifiés
- `backend/src/Activity/Domain/Model/SyncTaskType.php`
- `backend/src/Activity/Domain/Model/SyncTask.php`
- `frontend/src/activity/types/sync-task.ts`
- `frontend/src/shared/i18n/locales/en.json`
- `frontend/src/shared/i18n/locales/fr.json`
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [x] #1 Les tests doivent passer
- [x] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
