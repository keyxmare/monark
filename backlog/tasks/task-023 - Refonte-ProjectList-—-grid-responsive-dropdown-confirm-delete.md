---
id: TASK-023
title: 'Refonte ProjectList — grid responsive, dropdown, confirm delete'
status: To Do
assignee: []
created_date: '2026-03-13 12:08'
labels:
  - frontend
  - catalog
  - ux
  - refacto
dependencies: []
references:
  - frontend/src/catalog/pages/ProjectList.vue
  - frontend/src/catalog/pages/ProviderList.vue
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
ProjectList utilise un tableau statique non responsive (160 lignes) alors que ProviderList utilise un grid de cards responsive avec dropdown menu et confirmation de suppression.

**Changements :**
1. Remplacer la table par un grid responsive (1/2/3 cols)
2. Chaque projet en card (nom, repo truncated, visibility badge, branch, counts)
3. DropdownMenu pour les actions (view/edit/delete)
4. ConfirmDialog avant suppression
5. Empty state riche (icône + texte + CTA)
6. Pagination
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 La liste affiche les projets en grid responsive (1 col mobile, 2 tablet, 3 desktop)
- [ ] #2 Chaque card affiche nom, repo (truncated), visibility badge, branch, tech stacks count
- [ ] #3 Les actions sont dans un DropdownMenu (view, edit, delete)
- [ ] #4 La suppression passe par un ConfirmDialog
- [ ] #5 L'empty state affiche une icône, un texte et un bouton de création
- [ ] #6 La pagination est fonctionnelle
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
