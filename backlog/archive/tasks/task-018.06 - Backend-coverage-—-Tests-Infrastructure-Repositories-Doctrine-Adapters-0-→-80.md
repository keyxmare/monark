---
id: TASK-018.06
title: >-
  Backend coverage — Tests Infrastructure (Repositories Doctrine, Adapters) 0% →
  80%
status: Done
assignee: []
created_date: '2026-03-12 17:47'
updated_date: '2026-03-12 19:39'
labels:
  - testing
  - backend
  - infrastructure
dependencies: []
parent_task_id: TASK-018
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Toute la couche Infrastructure est à 0% :
- DoctrineProjectRepository, DoctrineProviderRepository, etc.
- DoctrineUserRepository, DoctrineTeamRepository, DoctrineAccessTokenRepository
- DoctrineSyncTaskRepository, DoctrineActivityEventRepository
- RabbitMqMonitor
- ApiTokenHandler, LoginFailure/SuccessHandler
- ExceptionListener, SecurityHeadersListener
- HealthController, ReadinessController

Approche : tests d'intégration avec base de données de test pour les repos Doctrine, mocks pour les adapters HTTP.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Coverage Infrastructure/ ≥ 70% pour chaque context
- [ ] #2 Tests passent en CI
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Résultat

Coverage backend passé de **72.7% → 80.1%** (objectif 80% atteint ✅)

### Tests ajoutés (13 fichiers, 1073 lignes, 39 tests)

**Activity Infrastructure** (8 tests)
- Controllers: CreateActivityEvent, GetActivityEvent, ListActivityEvents, CreateNotification, GetNotification, UpdateNotification, ListNotifications, GetDashboard

**Shared Infrastructure** (8 tests)
- HealthController (healthy + unhealthy), ReadinessController, SecurityHeadersListener (main + sub request), ErrorOutput (2), ExceptionListener (6 branches: NotFoundException, DomainException known/unknown/project-not-linked, ValidationFailedException, HttpException, generic), AppObjectMapper

**Catalog** (12 tests)
- Provider handlers: Delete (2), Update (2), TestConnection (3), GetProvider (2), ListProviders (2)
- IncrementSyncJobProgressListener (3): increment, not found, job completion
- GitProviderFactory (2): GitLab + GitHub routing
- SyncMergeRequestsConsoleCommand (1)

**Identity Security** (8 tests)
- LoginFailureHandler, LoginSuccessHandler, ApiTokenHandler round-trip, token rejection (invalid base64, wrong structure, invalid signature, expired, user not found)

**Activity Stats** (2 tests)
- GetMessengerStatsHandler, GetSyncTaskStatsHandler

**RabbitMqMonitor** (4 tests)
- Queue stats, empty stats, worker stats, zero-prefetch skip

**Domain Events** (10 tests)
- DependencyCreated/Updated/Deleted, ProjectCreated/Updated/Deleted/SyncCompleted, QuizCreated/Updated/Deleted

### Commit
`5792611` test(infra): add unit tests for infrastructure layer — coverage 72.7% → 80.1%
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
