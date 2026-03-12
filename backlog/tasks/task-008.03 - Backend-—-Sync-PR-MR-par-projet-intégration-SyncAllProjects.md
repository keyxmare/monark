---
id: TASK-008.03
title: Backend — Sync PR/MR par projet + intégration SyncAllProjects
status: Done
assignee: []
created_date: '2026-03-12 08:02'
updated_date: '2026-03-12 08:16'
labels:
  - backend
  - catalog
  - async
dependencies:
  - TASK-008.02
references:
  - >-
    backend/src/Catalog/Application/CommandHandler/SyncProjectMetadataHandler.php
  - backend/src/Catalog/Application/Command/SyncProjectMetadataCommand.php
  - backend/src/Catalog/Application/CommandHandler/SyncAllProjectsHandler.php
  - backend/src/Catalog/Domain/Event/ProjectMetadataSyncedEvent.php
parent_task_id: TASK-008
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Créer la commande de sync des PR/MR pour un projet et l'intégrer au flux SyncAllProjects.

**Command `SyncMergeRequestsCommand`** :
- projectId (string)

**Handler `SyncMergeRequestsHandler`** :
1. Récupère le projet + provider (ignore si pas de provider/externalId, comme SyncProjectMetadataHandler)
2. Appelle `listMergeRequests(provider, externalId, state='all')` — récupère toutes les MR
3. Pour chaque RemoteMergeRequest :
   - Cherche par `findByExternalIdAndProject(externalId, projectId)`
   - Si existe : met à jour les champs (status, title, reviewers, labels, additions, deletions, mergedAt, closedAt)
   - Si n'existe pas : crée une nouvelle MergeRequest
4. Émet un `MergeRequestsSyncedEvent(projectId, created: int, updated: int)` avec les compteurs

**Intégration SyncAllProjectsHandler** :
- Dispatche `SyncMergeRequestsCommand` en async (DispatchAfterCurrentBusStamp) pour chaque projet, en plus de ScanProjectCommand et SyncProjectMetadataCommand

**DTO `SyncMergeRequestsOutput`** :
- created (int), updated (int)

**Patterns** :
- Même pattern que SyncProjectMetadataHandler : silently ignore si pas de provider
- AsMessageHandler sur command.bus
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 SyncMergeRequestsHandler récupère les MR/PR du provider et crée/met à jour les entités MergeRequest
- [x] #2 Les MR existantes sont mises à jour (upsert via findByExternalIdAndProject), pas de doublons
- [x] #3 Les MR qui n'existent plus côté remote ne sont pas supprimées (on garde l'historique)
- [x] #4 Un MergeRequestsSyncedEvent est émis avec les compteurs created/updated
- [x] #5 Si le projet n'a pas de provider ou externalId, la commande est ignorée sans erreur
- [x] #6 SyncAllProjectsHandler dispatche SyncMergeRequestsCommand en async pour chaque projet
- [x] #7 Tests Pest : sync avec créations, sync avec updates, projet sans provider ignoré, intégration SyncAllProjects dispatch count
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Fichiers créés
- `backend/src/Catalog/Application/Command/SyncMergeRequestsCommand.php` — Commande avec projectId
- `backend/src/Catalog/Domain/Event/MergeRequestsSyncedEvent.php` — Event avec compteurs created/updated
- `backend/src/Catalog/Application/CommandHandler/SyncMergeRequestsHandler.php` — Upsert MR depuis remote, émet event
- `backend/tests/Unit/Catalog/Application/CommandHandler/SyncMergeRequestsHandlerTest.php` — 4 tests

## Fichiers modifiés
- `backend/src/Catalog/Application/CommandHandler/SyncAllProjectsHandler.php` — Dispatch SyncMergeRequestsCommand async
- `backend/config/services.yaml` — Wiring eventBus pour SyncMergeRequestsHandler
- `backend/tests/Unit/.../SyncAllProjectsHandlerTest.php` — Dispatch counts 6→9 et 4→6

## Tests
- 4 nouveaux tests (create, update, no provider ignored, unknown project ignored)
- 211 tests au total, 0 failures
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [x] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [x] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
