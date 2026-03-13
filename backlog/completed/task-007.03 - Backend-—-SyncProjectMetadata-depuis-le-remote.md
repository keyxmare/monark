---
id: TASK-007.03
title: Backend — SyncProjectMetadata depuis le remote
status: Done
assignee:
  - claude
created_date: '2026-03-11 19:58'
updated_date: '2026-03-12 07:38'
labels:
  - backend
  - catalog
  - git-provider
dependencies:
  - TASK-007.01
references:
  - backend/src/Catalog/Domain/Port/GitProviderInterface.php
  - backend/src/Catalog/Infrastructure/GitProvider/GitLabClient.php
  - backend/src/Catalog/Application/CommandHandler/ScanProjectHandler.php
parent_task_id: TASK-007
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Synchroniser les métadonnées des projets depuis le provider distant (GitLab/GitHub) lors d'une sync globale.

**Contexte** :
Actuellement, les métadonnées d'un projet (name, description, visibility, defaultBranch) ne sont écrites qu'à l'import initial. Si le projet est renommé ou sa visibility change côté GitLab, Monark ne le sait pas.

**À créer** :
- `SyncProjectMetadataCommand` : contient le projectId
- `SyncProjectMetadataHandler` :
  1. Récupère le projet + son provider
  2. Appelle l'API distante pour récupérer les infos actuelles du projet (via externalId)
  3. Compare et met à jour name, description, visibility, defaultBranch si changés
  4. Émet un `ProjectMetadataSyncedEvent` si des changements sont détectés (contient les champs modifiés)
- Ajouter `getProject(Provider, externalId): RemoteProject` à `GitProviderInterface`
- Implémenter dans `GitLabClient` : `GET /api/v4/projects/{externalId}`

**Intégration avec 007.01** :
- `SyncAllProjectsHandler` dispatche aussi un `SyncProjectMetadataCommand` en async pour chaque projet (en plus du scan)

**Event listener** (optionnel, dans 007.02) :
- `ProjectMetadataSyncedEvent` peut générer une SyncTask type=metadata_change si la visibility a changé
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 GitProviderInterface expose une méthode getProject(Provider, externalId): RemoteProject
- [x] #2 GitLabClient implémente getProject via GET /api/v4/projects/{externalId}
- [x] #3 SyncProjectMetadataHandler met à jour name, description, visibility, defaultBranch si différents du remote
- [x] #4 Un ProjectMetadataSyncedEvent est émis uniquement si au moins un champ a changé, avec la liste des champs modifiés
- [x] #5 Si le projet n'a pas de provider ou d'externalId, la commande est ignorée sans erreur
- [x] #6 SyncAllProjectsHandler dispatche SyncProjectMetadataCommand en async en plus de ScanProjectCommand
- [x] #7 Tests Pest : metadata mise à jour, pas d'event si rien n'a changé, projet sans provider ignoré, intégration GitLabClient
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
## Plan d'implémentation\n\n1. **GitProviderInterface** — ajouter `getProject(Provider, string $externalId): RemoteProject`\n2. **GitLabClient::getProject()** — `GET /api/v4/projects/{externalId}`\n3. **GitHubClient::getProject()** — `GET /repos/{full_name}` via slug du projet\n4. **SyncProjectMetadataCommand** — nouveau command avec `projectId`\n5. **ProjectMetadataSyncedEvent** — event domain avec projectId + changedFields array\n6. **SyncProjectMetadataHandler** — récupère projet+provider, appelle getProject, compare name/description/visibility/defaultBranch, met à jour si différent, émet event\n7. **SyncAllProjectsHandler** — dispatche SyncProjectMetadataCommand en async en plus de ScanProjectCommand\n8. **Tests Pest** — handler (metadata mise à jour, pas d'event si rien changé, projet sans provider ignoré), client GitLab/GitHub getProject
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
GitHubClient::getProject implémenté en plus de GitLabClient (via GET /repos/{full_name})

Tous les stubs GitProviderInterface dans les tests existants mis à jour pour inclure la nouvelle méthode getProject

190 tests passent, 0 failures
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Résumé\n\n### Fichiers créés\n- `src/Catalog/Application/Command/SyncProjectMetadataCommand.php`\n- `src/Catalog/Domain/Event/ProjectMetadataSyncedEvent.php`\n- `src/Catalog/Application/CommandHandler/SyncProjectMetadataHandler.php`\n- `tests/Unit/Catalog/Application/CommandHandler/SyncProjectMetadataHandlerTest.php`\n- `tests/Unit/Catalog/Infrastructure/GitProvider/GitLabClientTest.php`\n\n### Fichiers modifiés\n- `GitProviderInterface` — ajout `getProject(Provider, externalId): RemoteProject`\n- `GitLabClient` — implémentation `getProject` via `GET /api/v4/projects/{id}`\n- `GitHubClient` — implémentation `getProject` via `GET /repos/{full_name}`\n- `SyncAllProjectsHandler` — dispatch `SyncProjectMetadataCommand` en async pour chaque projet\n- Tests existants mis à jour pour la nouvelle méthode d'interface (CreateProviderHandlerTest, ListRemoteProjectsHandlerTest, ProjectScannerTest, SyncAllProjectsHandlerTest, GitHubClientTest)\n\n### Résultats\n- 190 tests, 516 assertions, 0 failures\n- 5 tests handler (metadata update, no-change, no-provider, unknown project, partial change)\n- 3 tests GitLabClient::getProject\n- 2 tests GitHubClient::getProject\n- 2 tests SyncAllProjectsHandler mis à jour (dispatch count x2)
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
