---
id: TASK-020.02
title: PR Comment — commentaire automatique coverage sur chaque PR
status: Done
assignee: []
created_date: '2026-03-13 07:24'
updated_date: '2026-03-13 07:27'
labels:
  - ci
  - devx
dependencies:
  - TASK-020.01
references:
  - .github/workflows/ci.yml
parent_task_id: TASK-020
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Ajouter un job CI qui poste (ou met à jour) un commentaire sur chaque PR avec un tableau récapitulatif des métriques coverage.

**Approche :**
- Nouveau job `coverage-report` qui dépend de `test-backend` et `test-frontend`
- Récupérer les artifacts coverage des deux jobs (clover.xml + coverage-summary.json)
- Construire un commentaire markdown avec tableau :
  ```
  ## Coverage Report
  | Stack | Lines | Statements | Gate |
  |---|---|---|---|
  | Backend (PHP) | 80.1% | 82.3% | ≥ 80% ✅ |
  | Frontend (TS) | 72.5% | 70.1% | — |
  | Mutation | 76.5% | — | ≥ 75% ✅ |
  ```
- Utiliser `gh pr comment` avec un marqueur HTML (`<!-- coverage-report -->`) pour mettre à jour le commentaire existant au lieu d'en créer un nouveau à chaque push

**Permissions requises :** `pull-requests: write` sur le workflow

**Pré-requis :** TASK-020.01 doit être fait (les artifacts coverage doivent être générés)

**Contrainte :** Le job ne doit s'exécuter que sur `pull_request`, pas sur `push` vers main.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Un commentaire coverage est posté automatiquement sur chaque PR
- [x] #2 Le commentaire contient un tableau avec les % coverage backend, frontend et mutation
- [x] #3 Le commentaire est mis à jour (pas dupliqué) à chaque nouveau push sur la PR
- [x] #4 Le commentaire indique visuellement si les gates sont passés (✅/❌)
- [x] #5 Le job ne s'exécute que sur l'événement `pull_request`
- [x] #6 Le job échoue gracieusement si les artifacts coverage ne sont pas disponibles
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Job `coverage-report` ajouté au workflow CI.\n\n- S'exécute uniquement sur `pull_request`, après `test-backend` + `test-frontend`\n- Télécharge les artifacts coverage via `actions/download-artifact@v4` (avec `continue-on-error`)\n- Parse clover.xml (Python XML) et coverage-summary.json (Python JSON)\n- Construit un commentaire markdown avec tableau + indicateurs gate (✅/❌)\n- Utilise un marqueur HTML `<!-- coverage-report -->` pour upsert (pas de doublons)\n- Permission `pull-requests: write` ajoutée au workflow
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
