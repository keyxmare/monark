---
id: TASK-020.01
title: CI Job Summary — afficher le coverage dans le résumé workflow
status: Done
assignee: []
created_date: '2026-03-13 07:24'
updated_date: '2026-03-13 07:27'
labels:
  - ci
  - quality
dependencies: []
references:
  - .github/workflows/ci.yml
parent_task_id: TASK-020
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Modifier le workflow CI pour que les jobs `test-backend` et `test-frontend` exportent les rapports coverage et écrivent le pourcentage dans `$GITHUB_STEP_SUMMARY`.

**Backend (Pest) :**
- Ajouter `XDEBUG_MODE=coverage` et `--coverage-clover coverage/clover.xml` à la commande Pest
- Parser le XML Clover pour extraire `coveredstatements / statements` en %
- Écrire le résultat en markdown dans `$GITHUB_STEP_SUMMARY`

**Frontend (Vitest) :**
- Ajouter `--coverage.reporter=json-summary` au script `test:coverage`
- Parser `coverage/coverage-summary.json` pour extraire `total.lines.pct`
- Écrire le résultat dans `$GITHUB_STEP_SUMMARY`

**Fichier à modifier :** `.github/workflows/ci.yml`

**Contrainte :** garder le gate `--min=80` existant sur le backend. Le summary est informatif, pas bloquant au-delà du gate.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le job test-backend génère un fichier `clover.xml` via `--coverage-clover`
- [x] #2 Le job test-frontend génère un fichier `coverage-summary.json` via `--coverage.reporter=json-summary`
- [x] #3 Le Job Summary de test-backend affiche le % coverage backend (ex: "Backend Coverage: 80.1%")
- [x] #4 Le Job Summary de test-frontend affiche le % coverage frontend (ex: "Frontend Coverage: 72.5%")
- [x] #5 Le gate `--min=80` backend reste actif et fait échouer le job si le seuil n'est pas atteint
- [x] #6 Le workflow fonctionne sur push et pull_request
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Workflow CI modifié pour générer et afficher le coverage.\n\n- **test-backend** : `--coverage-clover coverage/clover.xml` + parsing PHP du XML Clover → Job Summary\n- **test-frontend** : `--coverage.reporter=json-summary` + parsing Node du JSON → Job Summary\n- Artifacts uploadés (clover.xml + coverage-summary.json) avec rétention 7j pour TASK-020.02\n- Gate `--min=80` conservé\n- Testé localement : backend 82.7%, frontend 16.37%
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
