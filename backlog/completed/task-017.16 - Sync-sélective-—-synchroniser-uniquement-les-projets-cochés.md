---
id: TASK-017.16
title: Sync sélective — synchroniser uniquement les projets cochés
status: Done
assignee: []
created_date: '2026-03-12 16:11'
updated_date: '2026-03-13 07:36'
labels:
  - frontend
  - backend
  - catalog
dependencies:
  - TASK-017.11
parent_task_id: TASK-017
priority: low
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Item #17 — Permettre de cocher des projets déjà importés et ne synchroniser que ceux-là, au lieu du "sync all" global.

## Backend
- Nouveau endpoint ou paramètre sur l'existant : `POST /api/catalog/providers/{id}/sync` avec body `{ projectIds: [...] }`
- Créer un SyncJob avec `totalProjects` = nombre de projets sélectionnés
- Dispatcher les commandes de sync uniquement pour les projets choisis

## Frontend
- Afficher des checkboxes aussi sur les projets déjà importés
- Nouveau bouton "Synchroniser la sélection (N)" visible quand des projets importés sont cochés
- Réutiliser `useSyncProgress` pour le suivi temps réel

## Note
Dépend de TASK-017.11 (select all) pour la cohérence UX des checkboxes.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Checkboxes sur les projets déjà importés
- [ ] #2 Bouton "Synchroniser la sélection (N)" visible quand sélection active
- [ ] #3 Backend accepte une liste de projectIds à synchroniser
- [ ] #4 SyncJob créé avec le bon total
- [ ] #5 Suivi temps réel via toast + Mercure
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
