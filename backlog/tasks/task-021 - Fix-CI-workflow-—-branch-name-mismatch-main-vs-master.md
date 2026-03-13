---
id: TASK-021
title: Fix CI workflow — branch name mismatch (main vs master)
status: To Do
assignee: []
created_date: '2026-03-13 07:38'
labels: []
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Le workflow `.github/workflows/ci.yml` est configuré pour écouter sur `branches: [main]` (push + pull_request) mais la branche principale du repo est `master`. Résultat : la CI ne se déclenche jamais.

**Fix :** remplacer `main` par `master` dans les triggers `on.push.branches` et `on.pull_request.branches`.
<!-- SECTION:DESCRIPTION:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
