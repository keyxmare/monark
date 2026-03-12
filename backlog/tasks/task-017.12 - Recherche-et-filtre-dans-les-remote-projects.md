---
id: TASK-017.12
title: Recherche et filtre dans les remote projects
status: To Do
assignee: []
created_date: '2026-03-12 16:11'
labels:
  - frontend
  - ui/ux
  - catalog
dependencies: []
parent_task_id: TASK-017
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Item #13 — Pas de barre de recherche pour filtrer les projets distants. Indispensable pour les comptes avec beaucoup de repos.

## Approche
- Barre de recherche au-dessus de la liste des remote projects
- Filtrage côté client sur la page courante (par nom, slug)
- Optionnel : filtre par visibilité (public/private) et par statut d'import (importé/non importé)
- Debounce 300ms sur la saisie

## Note
Si l'API le supporte, passer le terme de recherche au backend pour un filtrage cross-pages.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Barre de recherche au-dessus de la liste remote projects
- [ ] #2 Filtrage par nom et slug
- [ ] #3 Debounce 300ms
- [ ] #4 Résultats mis à jour en temps réel
- [ ] #5 Optionnel : filtres visibilité et statut import
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
