---
id: TASK-018
title: Code quality — atteindre 80% coverage et 0 erreurs statiques
status: To Do
assignee: []
created_date: '2026-03-12 17:47'
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
- [ ] #1 PHPStan : 0 erreurs
- [ ] #2 ESLint : 0 erreurs
- [ ] #3 Backend coverage ≥ 80%
- [ ] #4 Frontend coverage stores ≥ 90%, composables ≥ 80%
- [ ] #5 Mutation score ≥ 75%
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
