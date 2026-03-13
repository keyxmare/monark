---
id: TASK-020
title: Coverage reporting — CI visibility + intégration Monark
status: To Do
assignee: []
created_date: '2026-03-13 07:24'
labels:
  - ci
  - quality
  - devx
dependencies: []
references:
  - .github/workflows/ci.yml
  - backend/phpunit.xml.dist
  - frontend/vitest.config.ts
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Rendre les métriques de coverage (backend + frontend + mutation) visibles dans la CI et exploitables dans Monark, sans dépendance externe type Codecov.

**État actuel :**
- CI exécute les tests avec `--min=80` (backend) et `pnpm test:coverage` (frontend) mais ne persiste ni n'affiche les résultats
- Aucun feedback coverage sur les PR
- Monark ne track pas les métriques de build

**Objectif :**
1. Afficher le coverage dans le résumé de chaque workflow run (Job Summary)
2. Poster un commentaire coverage sur chaque PR
3. Permettre à Monark d'ingérer et tracker les métriques coverage dans le temps

**Outils natifs utilisés :**
- Pest : `--coverage-clover` (XML Clover)
- Vitest : `--coverage.reporter=json-summary`
- GitHub Actions : `$GITHUB_STEP_SUMMARY`, `gh pr comment`, artifacts
- Monark API : nouveau endpoint d'ingestion de métriques
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Chaque workflow run affiche le % coverage backend et frontend dans le Job Summary
- [ ] #2 Chaque PR reçoit un commentaire automatique avec le tableau des métriques coverage
- [ ] #3 Monark peut ingérer et afficher les métriques coverage par projet dans le temps
<!-- AC:END -->
