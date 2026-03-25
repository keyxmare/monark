---
id: TASK-097
title: >-
  Les filtres doivent impacter le score de santé sur Stacks techniques et
  Dépendances
status: Done
assignee: []
created_date: '2026-03-18 22:44'
updated_date: '2026-03-18 22:49'
labels:
  - feature
  - UX
  - dependency
  - catalog
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Actuellement le score de santé (barre de progression + badges) est calculé sur l'ensemble des données, indépendamment des filtres actifs. Quand on filtre par projet ou par framework, le score devrait refléter uniquement les éléments filtrés.

## Pages concernées
- **Stacks techniques** : le healthScore doit refléter les stacks filtrées
- **Dépendances** : le healthScore (endpoint `/api/dependency/stats`) doit accepter les mêmes filtres (projet, package manager, type) et retourner les stats filtrées

## Implémentation
- L'endpoint `GET /api/dependency/stats` doit accepter des query params optionnels (`project_id`, `package_manager`, `type`)
- Le frontend passe les filtres actifs à l'endpoint stats
- Pour les stacks techniques (côté frontend), recalculer le score sur les données filtrées
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le score de santé des dépendances reflète les filtres actifs
- [x] #2 Le score de santé des stacks techniques reflète les filtres actifs
- [x] #3 L'endpoint stats accepte des filtres optionnels
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
**Stacks techniques** : le healthScore est recalculé sur `filteredStacks` au lieu de `techStackStore.techStacks`. Les filtres (framework, provider, statut) impactent directement le score.

**Dépendances** : l'endpoint `GET /api/dependency/stats` accepte maintenant `project_id`, `package_manager`, `type` en query params. Le handler passe les filtres au repository. Le frontend recharge les stats via un `watch` sur les filtres actifs.
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
