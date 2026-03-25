---
id: TASK-094
title: >-
  DependencyList — le score de santé doit couvrir toutes les dépendances sans
  limite de pagination
status: Done
assignee: []
created_date: '2026-03-18 22:36'
updated_date: '2026-03-18 22:44'
labels:
  - bug
  - dependency
  - UX
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Le score de santé (% à jour) est calculé sur les 1000 premières dépendances chargées côté frontend. Avec plus de 1000 dépendances, le pourcentage est faux.

## Options de fix
1. **Endpoint backend dédié** `GET /api/dependency/stats` qui retourne `{ total, upToDate, outdated, totalVulnerabilities }` sans pagination — le plus propre et performant
2. **Charger tout côté frontend** avec un `per_page` très élevé — simple mais pas scalable

L'option 1 est recommandée car le calcul d'agrégat est un `COUNT` SQL, pas besoin de charger toutes les entités.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le score de santé est calculé sur l'ensemble des dépendances
- [x] #2 Le pourcentage est correct même avec plus de 1000 dépendances
- [x] #3 Un endpoint backend dédié retourne les stats agrégées
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Endpoint backend `GET /api/dependency/stats` qui retourne `{ total, upToDate, outdated, totalVulnerabilities }` via des COUNT SQL. Le frontend appelle cet endpoint au lieu de calculer sur les données paginées.
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
