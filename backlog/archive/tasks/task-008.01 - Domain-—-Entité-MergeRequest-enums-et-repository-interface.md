---
id: TASK-008.01
title: 'Domain — Entité MergeRequest, enums et repository interface'
status: Done
assignee: []
created_date: '2026-03-12 08:01'
updated_date: '2026-03-12 08:10'
labels:
  - backend
  - catalog
  - domain
dependencies: []
references:
  - backend/src/Catalog/Domain/Model/Pipeline.php
  - backend/src/Catalog/Domain/Model/PipelineStatus.php
  - backend/src/Catalog/Domain/Repository/PipelineRepositoryInterface.php
  - >-
    backend/src/Catalog/Infrastructure/Persistence/Doctrine/DoctrinePipelineRepository.php
parent_task_id: TASK-008
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Créer l'entité MergeRequest et ses value objects dans le bounded context Catalog.

**Entité `MergeRequest`** (table `catalog_merge_requests`) :
- id (UUID, PK)
- externalId (string) — numéro/ID du PR/MR côté provider
- title (string 255)
- description (text, nullable)
- sourceBranch (string 255)
- targetBranch (string 255)
- status (enum MergeRequestStatus)
- author (string 255) — username de l'auteur
- url (string 500) — lien vers le PR/MR sur le provider
- additions (int, nullable) — lignes ajoutées
- deletions (int, nullable) — lignes supprimées
- reviewers (json) — liste des usernames reviewers
- labels (json) — liste des labels
- mergedAt (datetime, nullable)
- closedAt (datetime, nullable)
- createdAt (datetime)
- updatedAt (datetime)
- project (ManyToOne → Project, onDelete CASCADE)

**Enum `MergeRequestStatus`** :
- Open, Merged, Closed, Draft

**Repository Interface** `MergeRequestRepositoryInterface` :
- findById(Uuid): ?MergeRequest
- findByProjectId(Uuid, page, perPage, ?status): MergeRequest[]
- findByExternalIdAndProject(string externalId, Uuid projectId): ?MergeRequest
- countByProjectId(Uuid, ?status): int
- findAll(page, perPage): MergeRequest[]
- count(): int
- save(MergeRequest): void
- delete(MergeRequest): void

**Doctrine Repository** `DoctrineMergeRequestRepository` implémentant l'interface.

**Migration Doctrine** pour créer la table avec index sur (project_id, status).

**Patterns à suivre** :
- Même structure que `Pipeline` : entity, enum, repository interface, doctrine repository
- Collection sur Project : `$mergeRequests` OneToMany
- Factory method `MergeRequest::create(...)` + méthode `update(...)`
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 L'entité MergeRequest est mappée Doctrine avec tous les champs décrits
- [x] #2 L'enum MergeRequestStatus contient Open, Merged, Closed, Draft
- [x] #3 MergeRequestRepositoryInterface expose findByProjectId avec filtre status optionnel et findByExternalIdAndProject
- [x] #4 DoctrineMergeRequestRepository implémente l'interface complète
- [x] #5 Project a une collection OneToMany mergeRequests vers MergeRequest
- [x] #6 La migration crée la table catalog_merge_requests avec index sur (project_id, status)
- [x] #7 Tests Pest : création entité, update, repository stub
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Fichiers créés
- `backend/src/Catalog/Domain/Model/MergeRequest.php` — Entité avec 18 champs, factory `create()`, méthode `update()` sélective
- `backend/src/Catalog/Domain/Model/MergeRequestStatus.php` — Enum (Open, Merged, Closed, Draft)
- `backend/src/Catalog/Domain/Repository/MergeRequestRepositoryInterface.php` — Interface avec findByProjectId (filtre status), findByExternalIdAndProject, delete
- `backend/src/Catalog/Infrastructure/Persistence/Doctrine/DoctrineMergeRequestRepository.php` — Implémentation Doctrine complète
- `backend/migrations/Version20260312080930.php` — Migration CREATE TABLE avec index composite (project_id, status)
- `backend/tests/Unit/Catalog/Domain/Model/MergeRequestTest.php` — 4 tests (create, nullable, update sélectif, merge timestamps)
- `backend/tests/Unit/Catalog/Domain/Model/MergeRequestStatusTest.php` — 3 tests (cases, from, tryFrom)

## Fichiers modifiés
- `backend/src/Catalog/Domain/Model/Project.php` — Ajout collection OneToMany `$mergeRequests`
- `backend/config/services.yaml` — Alias MergeRequestRepositoryInterface → DoctrineMergeRequestRepository

## Tests
- 7 nouveaux tests, 44 assertions
- 197 tests au total, 0 failures
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [x] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [x] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
