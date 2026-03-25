---
id: TASK-083
title: >-
  DependencyList — les agrégats et le score ne couvrent que la première page de
  résultats
status: Done
assignee: []
created_date: '2026-03-18 22:11'
updated_date: '2026-03-18 22:14'
labels:
  - bug
  - dependency
  - UX
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Les cartes d'agrégation par projet et le score de santé global ne montrent que les dépendances de la première page (100 premières). Les projets qui n'ont des dépendances que sur les pages suivantes n'apparaissent pas dans les cartes, et le pourcentage « à jour » est faux.\n\nLe fix : charger toutes les dépendances pour les agrégats (requête sans pagination ou per_page très élevé), ou créer un endpoint backend dédié pour les stats agrégées.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Les cartes d'agrégation couvrent toutes les dépendances, pas seulement la première page
- [x] #2 Le score de santé global est calculé sur l'ensemble des dépendances
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Analyse de code statique doit passer
- [ ] #6 Deptrac doit passer
<!-- DOD:END -->
