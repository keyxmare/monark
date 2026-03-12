---
id: TASK-018.06
title: >-
  Backend coverage — Tests Infrastructure (Repositories Doctrine, Adapters) 0% →
  80%
status: To Do
assignee: []
created_date: '2026-03-12 17:47'
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

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
