---
id: TASK-017.15
title: 'Indicateur de santé provider (stats synchro, latence API)'
status: To Do
assignee: []
created_date: '2026-03-12 16:11'
labels:
  - frontend
  - backend
  - catalog
dependencies: []
parent_task_id: TASK-017
priority: low
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Item #16 — Au-delà du simple statut `connected/pending/error`, enrichir la fiche provider avec des métriques de santé.

## Métriques proposées
- Uptime : depuis combien de temps le provider est connecté
- Taux de succès des synchros récentes (ex: 95% sur les 30 derniers jours)
- Latence API moyenne (dernière vérification)
- Nombre d'erreurs récentes

## Backend
- Ajouter un endpoint `/api/catalog/providers/{id}/health` ou enrichir le DTO existant
- Stocker les métriques au fil des synchros (ou les calculer à la volée)

## Frontend
- Section dédiée dans ProviderDetail avec mini-graphiques ou indicateurs visuels
- Badge de santé synthétique dans ProviderList (vert/orange/rouge)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Métriques de santé visibles sur ProviderDetail
- [ ] #2 Au minimum : uptime et taux de succès synchro
- [ ] #3 Badge de santé synthétique dans ProviderList
- [ ] #4 Données alimentées par le backend
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
