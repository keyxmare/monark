---
id: TASK-018.03
title: ESLint — Corriger les 141 erreurs frontend (perfectionist + a11y)
status: Done
assignee: []
created_date: '2026-03-12 17:47'
updated_date: '2026-03-12 18:47'
labels:
  - eslint
  - frontend
dependencies: []
parent_task_id: TASK-018
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Répartition par règle :
- `perfectionist/sort-objects` : 69 occurrences
- `perfectionist/sort-imports` : 52 occurrences
- `perfectionist/sort-union-types` : 8
- `perfectionist/sort-switch-case` : 4
- `vuejs-accessibility/form-control-has-label` : 3
- `vue/return-in-computed-property` : 1
- `perfectionist/sort-named-imports` : 1
- `perfectionist/sort-modules` : 1

La majorité (137) sont auto-fixables avec `pnpm run lint --fix`. Les 3 a11y et le `return-in-computed-property` nécessitent des corrections manuelles.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 pnpm run lint : 0 erreurs, 0 warnings
- [x] #2 Aucune régression de tests
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Résolu les 146 erreurs ESLint :
- 142 auto-fixées (perfectionist: sort-objects, sort-imports, sort-union-types, etc.)
- 4 manuelles : default case dans AppToast computed, aria-label sur 3 selects SyncTaskList

0 erreurs, 0 warnings. 114 tests frontend passent. Commit: `a224cda`
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [x] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [x] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
