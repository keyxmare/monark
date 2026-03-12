---
id: TASK-018.01
title: PHPStan — Corriger les 209 erreurs du context Catalog
status: Done
assignee: []
created_date: '2026-03-12 17:47'
updated_date: '2026-03-12 18:40'
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
- [x] #1 PHPStan 0 erreurs sur src/Catalog/
- [x] #2 Aucune régression de tests
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Résolu les 209 erreurs PHPStan du context Catalog (level max) en ajoutant des annotations de type précises :

- **GitHubClient/GitLabClient** (90 erreurs) : Shapes d'array précises pour les réponses API au lieu de `array<string, mixed>` — élimine tous les "Cannot cast mixed"
- **ProjectScanner** (80 erreurs) : Types `@param` pour les données JSON décodées (composer.json, package.json, composer.lock), extraction typée des dépendances/versions
- **Doctrine repositories** (15 erreurs) : `@var list<Entity>` sur les appels `getResult()`
- **Controllers** (12 erreurs) : Import et `@var` pour les DTOs de résultat (ProviderListOutput, etc.)
- **Petits fichiers** (12 erreurs) : Cast json_encode, split @param, type argument console

Commit: `10f61c4` — 19 fichiers modifiés, 220 tests passent.
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [x] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [x] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
