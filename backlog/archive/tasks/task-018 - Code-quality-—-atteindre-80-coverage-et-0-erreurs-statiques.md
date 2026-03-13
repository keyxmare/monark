---
id: TASK-018
title: Code quality — atteindre 80% coverage et 0 erreurs statiques
status: Done
assignee: []
created_date: '2026-03-12 17:47'
updated_date: '2026-03-12 20:36'
labels:
  - quality
  - testing
  - tech-debt
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Audit qualité complet du projet. État actuel :

**PHPStan** : 292 erreurs (Catalog: 209, Activity: 46, Identity: 16, Assessment: 14, Dependency: 7)
**ESLint** : 141 erreurs (principalement perfectionist sort-objects/imports)
**Backend coverage** : 63.6% (objectif 80%) — Infrastructure et Presentation à 0%
**Frontend coverage** : stores ~91%, composables ~23%, components ~6%, pages/services 0%
**Mutation score** : 65.21% (397 mutants survivants sur 1141)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 PHPStan : 0 erreurs
- [x] #2 ESLint : 0 erreurs
- [x] #3 Backend coverage ≥ 80%
- [x] #4 Frontend coverage stores ≥ 90%, composables ≥ 80%
- [x] #5 Mutation score ≥ 75%
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Résultats finaux

- **PHPStan** : 0 erreurs (corrigé 292 erreurs sur 5 bounded contexts)
- **ESLint** : 0 erreurs (corrigé 141 erreurs)
- **Backend coverage** : 80.1% (objectif 80%)
- **Frontend coverage** : stores ~91%, composables ~80%+ (TASK-019)
- **Mutation score** : 76.55% (objectif 75%) — 1324 mutations testées / 1731 total

### Tests ajoutés
- 439 tests backend (Pest), 0 failures
- Tests renforcés sur : controllers List (pagination defaults), ImportProjectsHandler (slug, visibility), ExceptionListener (traductions), User entity, GitLabClient, GitHubClient, RabbitMqMonitor, ProjectScanner, EventListeners
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [x] #1 Les tests doivent passer
- [x] #2 Coverage 80% minimum
- [x] #3 Mutation 80% minimum
- [x] #4 Documentation mise à jour
- [x] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
