---
id: TASK-007.03
title: Backend — SyncProjectMetadata depuis le remote
status: To Do
assignee: []
created_date: '2026-03-11 19:58'
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
- [ ] #1 GitProviderInterface expose une méthode getProject(Provider, externalId): RemoteProject
- [ ] #2 GitLabClient implémente getProject via GET /api/v4/projects/{externalId}
- [ ] #3 SyncProjectMetadataHandler met à jour name, description, visibility, defaultBranch si différents du remote
- [ ] #4 Un ProjectMetadataSyncedEvent est émis uniquement si au moins un champ a changé, avec la liste des champs modifiés
- [ ] #5 Si le projet n'a pas de provider ou d'externalId, la commande est ignorée sans erreur
- [ ] #6 SyncAllProjectsHandler dispatche SyncProjectMetadataCommand en async en plus de ScanProjectCommand
- [ ] #7 Tests Pest : metadata mise à jour, pas d'event si rien n'a changé, projet sans provider ignoré, intégration GitLabClient
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
