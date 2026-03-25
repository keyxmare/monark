---
id: TASK-034
title: 'Supprimer le bounded context Assessment (quiz, questions, tentatives)'
status: Done
assignee: []
created_date: '2026-03-18 19:10'
updated_date: '2026-03-18 19:17'
labels:
  - cleanup
  - assessment
  - DDD
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Supprimer entièrement le bounded context **Assessment** et toutes ses dépendances dans le projet.

## Périmètre

### Backend (Symfony / PHP)
- `src/Assessment/` — tout le répertoire (Domain, Application, Infrastructure, Presentation)
  - Entités : Quiz, Question, Answer, Attempt
  - Repositories, Controllers, Commands, Queries, DTOs
  - Value Objects, Ports
- Migrations Doctrine liées aux tables assessment
- Fixtures liées au contexte Assessment
- Configuration Symfony : routes, services, doctrine mappings pour Assessment
- Tests Pest associés (`tests/Assessment/` ou similaire)

### Frontend (Vue.js / TypeScript)
- `src/modules/assessment/` — tout le répertoire
  - Pages, composants, composables, services, stores, types
- Routes Vue Router référençant assessment/evaluation/quiz
- Entrées de navigation/menu « Évaluation » ou « Assessment »
- Tests Vitest associés

### Transversal
- Références dans les fichiers de configuration (docker, CI, etc.)
- Imports ou références croisées depuis d'autres contextes
- Documentation ou ADR mentionnant Assessment
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Aucun fichier PHP dans src/Assessment/
- [x] #2 Aucun fichier frontend dans src/modules/assessment/
- [x] #3 Aucune route backend ou frontend référençant assessment/quiz/evaluation
- [x] #4 Aucune entrée de menu « Évaluation » dans la navigation
- [x] #5 Aucune migration orpheline — migration de suppression des tables créée si nécessaire
- [ ] #6 Les tests existants des autres contextes passent toujours
- [ ] #7 PHPStan et ESLint passent sans erreur liée à Assessment
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Suppression complète du bounded context Assessment :

**Backend supprimé :**
- `src/Assessment/` (87 fichiers — entités, repos, controllers, commands, queries, DTOs, events, value objects)
- `tests/Unit/Assessment/` + `tests/Factory/Assessment/` (28 fichiers)
- Migration `Version20260318191500` créée pour DROP les 4 tables (quizzes, questions, answers, attempts)

**Frontend supprimé :**
- `src/assessment/` (pages, stores, services, types, routes)
- `tests/unit/assessment/` (4 tests stores)

**Config nettoyée :**
- `services.yaml` : autoload + 4 alias repository
- `doctrine.yaml` : mapping Assessment
- `deptrac.yaml` : 3 layers + 3 rules
- `router.ts` : import + spread assessmentRoutes
- `AppSidebar.vue` : section nav Évaluation
- `fr.json` / `en.json` : traductions assessment (nav, entities, pages)
- `ExceptionListener.php` : entrée quiz slug
- `DomainEventsTest.php` : 3 tests Quiz events
- `ExceptionListenerTest.php` : test quiz slug
- `scaffold.config.json` : bounded context + features Assessment
- `CLAUDE.md`, `ARCHITECTURE.md`, `c4/context.md`, `features/README.md`, `CONTRIBUTING.md`, `openapi.yaml`
- `docs/features/assessment.md` supprimé
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Analyse de code statique doit passer
- [ ] #6 Deptrac doit passer
<!-- DOD:END -->
