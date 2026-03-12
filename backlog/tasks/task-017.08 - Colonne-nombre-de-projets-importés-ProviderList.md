---
id: TASK-017.08
title: Colonne nombre de projets importés (ProviderList)
status: Done
assignee: []
created_date: '2026-03-12 16:10'
updated_date: '2026-03-12 16:33'
labels:
  - frontend
  - backend
  - catalog
dependencies: []
parent_task_id: TASK-017
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Item #9 — Ajouter une colonne "Projets" dans le tableau ProviderList montrant le nombre de projets importés par provider. Info clé manquante actuellement.

## Backend
Vérifier si le count est déjà retourné par l'API `/api/catalog/providers`. Sinon, ajouter un champ `projectsCount` au DTO de sortie.

## Frontend
Ajouter la colonne entre "Status" et "Dernière sync".
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Colonne "Projets" visible dans le tableau ProviderList
- [x] #2 Affiche le nombre de projets importés par provider
- [x] #3 Donnée retournée par l'API (ajout DTO si nécessaire)
- [x] #4 Affichage "0" si aucun projet
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
