---
id: TASK-009
title: Filtrer les MR par statut actif par défaut (open/draft)
status: In Progress
assignee: []
created_date: '2026-03-12 09:06'
labels:
  - frontend
  - backend
  - catalog
dependencies: []
references:
  - frontend/src/catalog/pages/MergeRequestList.vue
  - frontend/src/catalog/pages/ProjectDetail.vue
  - frontend/src/catalog/stores/merge-request.ts
  - backend/src/Catalog/Application/QueryHandler/ListMergeRequestsHandler.php
  - >-
    backend/src/Catalog/Infrastructure/Persistence/Doctrine/DoctrineMergeRequestRepository.php
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Par défaut, afficher uniquement les MR/PR actives (open + draft). Les MR merged et closed doivent être masquées mais accessibles via un filtre configurable.

## Frontend

### Filtre multi-statut
- Remplacer le select simple par un filtre preset : "Actives" (open+draft) par défaut, "Merged", "Closed", "All"
- Appliquer ce défaut sur `MergeRequestList.vue` et le tab MR de `ProjectDetail.vue`
- Le choix doit être persisté (localStorage ou query param)

### Backend API
- Le endpoint `GET /api/catalog/projects/{projectId}/merge-requests` accepte déjà `?status=open` mais un seul statut à la fois
- Ajouter le support multi-statut : `?status=open,draft` (ou un preset `?status=active`)

### UX
- Badge compteur dans le tab ProjectDetail : ne compter que les MR actives
- Le filtre doit être visible et intuitif (boutons toggle ou select)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 MR open et draft affichées par défaut, merged/closed masquées
- [ ] #2 Filtre configurable pour voir merged, closed ou toutes les MR
- [ ] #3 Support multi-statut côté API (?status=open,draft ou ?status=active)
- [ ] #4 Compteur tab ProjectDetail basé sur les MR actives uniquement
- [ ] #5 Choix du filtre persisté entre les navigations
- [ ] #6 Tests unitaires backend (multi-statut) et frontend (store, défaut)
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
