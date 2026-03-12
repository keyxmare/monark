---
id: TASK-018.04
title: Backend coverage — Tests d'intégration controllers Catalog (0% → 80%)
status: To Do
assignee: []
created_date: '2026-03-12 17:47'
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

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
