---
id: TASK-017.09
title: ProviderList — Layout cards au lieu de tableau
status: Done
assignee: []
created_date: '2026-03-12 16:11'
updated_date: '2026-03-12 16:35'
labels:
  - frontend
  - ui/ux
  - catalog
dependencies:
  - TASK-017.04
  - TASK-017.06
  - TASK-017.08
parent_task_id: TASK-017
priority: low
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Item #10 — Avec un nombre limité de providers en général, un layout en cards (grille 2-3 colonnes) serait plus adapté et visuellement plus riche qu'un tableau.

## Design
Chaque card affiche :
- Icône + nom du provider
- Badge type (GitLab/GitHub/Bitbucket)
- Badge statut (connecté/erreur/en attente)
- URL (cliquable)
- Nombre de projets importés
- Dernière synchronisation
- Menu kebab (actions)

## Responsive
- 3 colonnes desktop
- 2 colonnes tablette
- 1 colonne mobile

## Note
Dépend de TASK-017.04 (dropdown), TASK-017.06 (icônes), TASK-017.08 (nb projets).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Layout en grille de cards responsives
- [x] #2 Chaque card affiche toutes les infos clés du provider
- [x] #3 3 colonnes desktop, 2 tablette, 1 mobile
- [x] #4 Menu actions intégré dans chaque card
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
