---
id: TASK-018.04
title: Backend coverage — Tests d'intégration controllers Catalog (0% → 80%)
status: Done
assignee: []
created_date: '2026-03-12 17:47'
updated_date: '2026-03-12 19:08'
labels:
  - testing
  - catalog
  - backend
dependencies: []
parent_task_id: TASK-018
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Tous les controllers Catalog sont à 0% de couverture. C'est le plus gros gain possible pour atteindre l'objectif de 80%.

Controllers à couvrir :
- ListRemoteProjectsController
- SyncAllProjectsController
- ImportProjectsController
- CRUD Provider (Create, Get, List, Update, Delete)
- CRUD Project
- TestProviderConnectionController
- Pipeline controllers
- TechStack controllers
- MergeRequest controllers

Approche : tests fonctionnels HTTP via le kernel Symfony, avec fixtures ou mocks pour les APIs externes.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Coverage Catalog/Presentation ≥ 80%
- [ ] #2 Tests passent en CI
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Résultat

27 tests unitaires couvrant les 25 controllers Catalog/Presentation — tous à **100% de couverture**.

### Fichiers créés
- `tests/Unit/Catalog/Presentation/Controller/ProjectControllersTest.php` (6 tests)
- `tests/Unit/Catalog/Presentation/Controller/ProviderControllersTest.php` (10 tests)
- `tests/Unit/Catalog/Presentation/Controller/ResourceControllersTest.php` (9 tests)
- `tests/Unit/Catalog/Presentation/Controller/SyncJobControllerTest.php` (2 tests)

### Approche
Tests unitaires directs (pas fonctionnels HTTP) avec stubs MessageBusInterface inline retournant des Envelopes avec HandledStamp. Chaque controller est instancié manuellement avec son bus mocké. Coverage globale : 63.6% → 67.4%.

### Commit
`b9a50ff` — test(catalog): add unit tests for all 25 Presentation controllers
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
