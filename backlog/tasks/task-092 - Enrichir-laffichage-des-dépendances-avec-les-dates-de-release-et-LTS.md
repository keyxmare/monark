---
id: TASK-092
title: Enrichir l'affichage des dépendances avec les dates de release et LTS
status: Done
assignee: []
created_date: '2026-03-18 22:36'
updated_date: '2026-03-18 23:41'
labels:
  - feature
  - dependency
  - UX
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Une fois les versions synchronisées depuis les registres, enrichir l'affichage des dépendances :

## Sur DependencyList
- Afficher la date de release de la version courante
- Afficher la date de release de la dernière version
- Afficher l'écart temporel entre les deux (même pattern humanisé que les stacks)
- Badge LTS si la dernière version est LTS

## Sur DependencyDetail
- Timeline des versions disponibles avec dates
- Indication visuelle de la position de la version courante dans la timeline

## Sur ProjectDetail onglet dépendances
- Même enrichissement que DependencyList
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Les dates de release sont affichées sur DependencyList
- [x] #2 L'écart temporel est affiché en format humanisé
- [x] #3 DependencyDetail montre les versions disponibles
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
**Backend** :
- `DependencyOutput` enrichi avec `currentVersionReleasedAt` et `latestVersionReleasedAt`
- `ListDependenciesHandler` interroge `DependencyVersionRepository` pour chaque dépendance pour récupérer les dates de release des versions courante et latest

**Frontend DependencyList** :
- Nouvelle colonne « Écart LTS » avec temps humanisé entre la date de release de la version courante et celle de la dernière version
- Couleur vert/orange/rouge selon l'écart (< 6 mois / 6-24 mois / > 2 ans)
- « À jour » en vert si pas outdated
- « — » si les dates ne sont pas encore synchronisées

**Type** : `Dependency` enrichi avec `currentVersionReleasedAt` et `latestVersionReleasedAt`
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
