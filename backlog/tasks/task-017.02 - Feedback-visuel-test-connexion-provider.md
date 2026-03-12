---
id: TASK-017.02
title: Feedback visuel test connexion provider
status: Done
assignee: []
created_date: '2026-03-12 16:10'
updated_date: '2026-03-12 16:20'
labels:
  - frontend
  - ui/ux
  - catalog
dependencies: []
parent_task_id: TASK-017
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Item #2 — Le résultat du test de connexion n'est affiché nulle part actuellement. Ajouter un toast (success/error) après le test de connexion, et optionnellement mettre à jour le badge de statut du provider en temps réel.

## Pages impactées
- ProviderList.vue → action "Tester"
- ProviderDetail.vue → bouton "Tester la connexion"
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Toast success si connexion OK avec nom du provider
- [x] #2 Toast error si connexion échouée avec message d'erreur
- [x] #3 Le badge statut du provider se met à jour après le test
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
