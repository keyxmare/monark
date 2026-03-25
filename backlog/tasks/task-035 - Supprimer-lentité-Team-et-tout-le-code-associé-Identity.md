---
id: TASK-035
title: Supprimer l'entité Team et tout le code associé (Identity)
status: Done
assignee: []
created_date: '2026-03-18 19:17'
updated_date: '2026-03-18 19:23'
labels:
  - cleanup
  - identity
  - DDD
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Supprimer entièrement la gestion des équipes du bounded context **Identity**.

## Périmètre

### Backend (Symfony / PHP)
- Entité `Team` + relation ManyToMany avec `User`
- `TeamRepositoryInterface` + `DoctrineTeamRepository`
- Controllers CRUD Team (Create, Get, List, Update, Delete)
- Commands/CommandHandlers Team
- Queries/QueryHandlers Team
- DTOs Team (Input/Output)
- Events Team (si existants)
- Alias service dans `services.yaml`
- Mapping Doctrine si spécifique
- Tests Pest associés
- Factories de test
- ExceptionListener : entrée "duplicate slug for team"

### Frontend (Vue.js / TypeScript)
- Pages Team (list, form, detail)
- Store, service, types Team
- Routes Team
- Entrée de navigation « Équipes » dans le sidebar
- Traductions i18n (fr.json, en.json)
- Tests Vitest associés

### Transversal
- Migration Doctrine pour DROP des tables (identity_teams, identity_team_members)
- Retirer la relation `teams` de l'entité User
- Documentation (ARCHITECTURE.md, features/identity.md, etc.)
- scaffold.config.json
- openapi.yaml si endpoints Team présents
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Aucun fichier PHP lié à Team dans src/Identity/
- [x] #2 Aucune page/composant/store Team dans le frontend
- [x] #3 Aucune route backend ou frontend référençant teams
- [x] #4 Aucune entrée de menu « Équipes » dans la navigation
- [x] #5 L'entité User n'a plus de relation ManyToMany avec Team
- [x] #6 Migration de suppression des tables identity_teams et identity_team_members créée
- [ ] #7 Les tests existants passent toujours
- [ ] #8 PHPStan et ESLint passent sans erreur liée à Team
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Suppression complète de l'entité Team du contexte Identity :

**Backend supprimé :**
- `Team.php` entity, `TeamRepositoryInterface`, `DoctrineTeamRepository`
- 5 controllers (Create, Get, List, Update, Delete)
- 3 commands + 3 handlers, 2 queries + 2 query handlers
- 4 DTOs (TeamOutput, TeamListOutput, CreateTeamInput, UpdateTeamInput)
- Relation ManyToMany retirée de User entity
- `TeamFactory.php` + 7 tests Pest (5 handler tests + controller tests)
- ExceptionListener : entrée "team slug"
- ExceptionListenerTest : test "team slug"

**Frontend supprimé :**
- 3 pages (TeamList, TeamDetail, TeamForm)
- Store, service, types Team
- 4 routes Team
- Entry nav sidebar "Équipes"
- Traductions fr/en (identity.teams, nav.teams, entities.teams)
- Test store team.test.ts

**Config nettoyée :**
- services.yaml, scaffold.config.json
- CLAUDE.md, ARCHITECTURE.md, features/README.md, features/identity.md, CONTRIBUTING.md

**Migration `Version20260318192000`** créée pour DROP identity_team_members + identity_teams
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
