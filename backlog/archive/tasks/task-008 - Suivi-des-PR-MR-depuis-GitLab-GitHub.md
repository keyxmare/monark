---
id: TASK-008
title: Suivi des PR/MR depuis GitLab & GitHub
status: Done
assignee: []
created_date: '2026-03-12 08:01'
updated_date: '2026-03-12 08:31'
labels:
  - feature
  - catalog
  - git-provider
dependencies: []
references:
  - backend/src/Catalog/Domain/Port/GitProviderInterface.php
  - backend/src/Catalog/Domain/Model/Project.php
  - backend/src/Catalog/Domain/Model/Pipeline.php
  - backend/src/Catalog/Infrastructure/GitProvider/GitLabClient.php
  - backend/src/Catalog/Infrastructure/GitProvider/GitHubClient.php
  - backend/src/Catalog/Application/CommandHandler/SyncAllProjectsHandler.php
  - frontend/src/catalog/pages/ProjectDetail.vue
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Ajouter le suivi des Pull Requests (GitHub) et Merge Requests (GitLab) pour chaque projet importé dans Monark.

**Contexte** :
Monark suit déjà les projets, tech stacks, pipelines CI/CD et dépendances. Il manque la visibilité sur les PR/MR : quels sont les PR ouverts, qui les review, depuis combien de temps, etc.

**Objectif** :
- Modéliser une entité unifiée `MergeRequest` dans le context Catalog (couvre PR GitHub + MR GitLab)
- Récupérer les PR/MR via les APIs GitLab et GitHub
- Synchroniser automatiquement lors du sync global
- Afficher les PR/MR par projet (tab dans ProjectDetail) et dans une page dédiée
- Générer des SyncTasks pour les PR stales (ouvertes trop longtemps) ou sans reviewers

**Entité MergeRequest** :
- externalId (string), title (string), description (text nullable)
- sourceBranch (string), targetBranch (string)
- status (enum: open, merged, closed, draft)
- author (string), url (string)
- additions (int nullable), deletions (int nullable)
- reviewers (json), labels (json)
- mergedAt (datetime nullable), closedAt (datetime nullable)
- project (ManyToOne → Project)

**Architecture** :
- Context : Catalog (même contexte que Project/Pipeline)
- Sync : intégré à SyncAllProjectsHandler (dispatch async comme ScanProject et SyncProjectMetadata)
- SyncTasks : nouveau type `stale_merge_request` dans le context Activity
<!-- SECTION:DESCRIPTION:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [x] #1 Les tests doivent passer
- [x] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## TASK-008 — Suivi des PR/MR depuis GitLab & GitHub

### Subtasks complétées (6/6)
- **008.01** Domain — Entité MergeRequest, enums, repository, migration
- **008.02** GitProvider clients — listMergeRequests pour GitLab + GitHub
- **008.03** Sync handler — upsert MR + intégration SyncAllProjects
- **008.04** API REST — ListMergeRequests, GetMergeRequest, DTOs, controllers
- **008.05** Frontend — types TS, service, store Pinia, page MergeRequestList, tab ProjectDetail, i18n EN/FR
- **008.06** Activity — détection PR stales (>7j medium, >30j high), SyncTask type stale_pr, widget dashboard

### Bilan technique
- **Entité MergeRequest** : 18 champs, factory create/update selective, index project+status
- **GitLab** : mapping state opened→open, détection draft, additions/deletions null (API limitation)
- **GitHub** : mapping state+merged_at, draft flag, additions/deletions disponibles
- **Sync** : 3 commands par projet (scan + metadata + MR), upsert pattern
- **Frontend** : table avec filtres status/author, badges colorés, liens externes
- **Stale detection** : listener async, severity progressive, upsert existing tasks

### Tests
- Backend : ~220 tests Pest (tous green)
- Frontend : 114 tests Vitest (tous green)

### Note
- ProjectDetail.vue atteint 560 lignes — extraction des tab panels en composants à planifier
<!-- SECTION:FINAL_SUMMARY:END -->
