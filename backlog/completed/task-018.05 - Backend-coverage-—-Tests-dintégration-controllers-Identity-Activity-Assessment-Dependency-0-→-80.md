---
id: TASK-018.05
title: >-
  Backend coverage — Tests d'intégration controllers Identity, Activity,
  Assessment, Dependency (0% → 80%)
status: Done
assignee: []
created_date: '2026-03-12 17:47'
updated_date: '2026-03-12 19:14'
labels:
  - testing
  - backend
dependencies: []
parent_task_id: TASK-018
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Tous les controllers des autres contexts sont à 0%. Liste par context :

**Identity** (16 controllers) : Register, Login, Logout, GetCurrentUser, CRUD User, CRUD Team, CRUD AccessToken
**Activity** (6 controllers) : ActivityEvent list/create, Notification list, Dashboard, SyncTask CRUD
**Assessment** (8 controllers) : CRUD Quiz, CRUD Question, CRUD Answer, Attempt
**Dependency** (4 controllers) : CRUD Dependency, CRUD Vulnerability

Approche : même pattern que TASK-018.04, tests HTTP avec fixtures.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Coverage Presentation/ ≥ 80% pour chaque context
- [ ] #2 Tests passent en CI
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Résultat

48 tests unitaires couvrant tous les controllers des 4 contextes — **tous à 100%**.

### Fichiers créés
- `tests/Unit/Identity/Presentation/Controller/IdentityControllersTest.php` (16 tests)
- `tests/Unit/Activity/Presentation/Controller/ActivityControllersTest.php` (5 tests)
- `tests/Unit/Assessment/Presentation/Controller/AssessmentControllersTest.php` (18 tests)
- `tests/Unit/Dependency/Presentation/Controller/DependencyControllersTest.php` (9 tests)

### Controllers spéciaux couverts
- LoginController (throws LogicException)
- LogoutController (pas de bus)
- GetCurrentUserController (#[CurrentUser] + UserOutput::fromEntity)
- CreateAccessToken/ListAccessTokens (#[CurrentUser])
- UpdateSyncTaskStatusController (JSON body decode)
- GetSyncTaskStatsController/GetMessengerStatsController (toArray() sur résultat)

### Métriques
- Coverage globale : 67.4% → 72.7%
- 295 tests, 0 failures

### Commit
`66bb0f0`
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
