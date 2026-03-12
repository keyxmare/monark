---
id: TASK-018.01
title: PHPStan — Corriger les 209 erreurs du context Catalog
status: To Do
assignee: []
created_date: '2026-03-12 17:47'
labels:
  - phpstan
  - catalog
  - backend
dependencies: []
parent_task_id: TASK-018
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Top fichiers :
- `ProjectScanner.php` : 80 erreurs (types mixtes dans les parsers de fichiers)
- `GitHubClient.php` : 48 erreurs (types array retournés par l'API)
- `GitLabClient.php` : 42 erreurs (idem)
- `DoctrineProjectRepository.php` : 8 erreurs
- `ImportProjectsInput.php` : 4 erreurs
- `MergeRequest.php` : 4 erreurs
- Reste : ~23 erreurs réparties

Principalement des `mixed` non typés provenant des réponses API et des array_map.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 PHPStan 0 erreurs sur src/Catalog/
- [ ] #2 Aucune régression de tests
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
