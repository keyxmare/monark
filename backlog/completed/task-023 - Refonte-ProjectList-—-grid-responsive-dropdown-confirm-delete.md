---
id: TASK-023
title: 'Refonte ProjectList — grid responsive, dropdown, confirm delete'
status: Done
assignee: []
created_date: '2026-03-13 12:08'
updated_date: '2026-03-13 12:25'
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
- [x] #1 La liste affiche les projets en grid responsive (1 col mobile, 2 tablet, 3 desktop)
- [x] #2 Chaque card affiche nom, repo (truncated), visibility badge, branch, tech stacks count
- [x] #3 Les actions sont dans un DropdownMenu (view, edit, delete)
- [x] #4 La suppression passe par un ConfirmDialog
- [x] #5 L'empty state affiche une icône, un texte et un bouton de création
- [x] #6 La pagination est fonctionnelle
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Refonte ProjectList : table statique remplacée par grid responsive de cards avec DropdownMenu, ConfirmDialog, empty state riche et pagination.\n\n**Changements** :\n- Grid responsive 1/2/3 cols (mobile/tablet/desktop)\n- Cards cliquables : nom, repo truncated, visibility badge, branch, tech stacks count\n- DropdownMenu (view/edit/delete) au lieu de liens inline\n- ConfirmDialog avant suppression\n- Empty state avec icône folder, texte d'aide et CTA\n- Pagination prev/next avec état désactivé\n- 3 clés i18n ajoutées (noProjectsHint, confirmDeleteTitle, confirmDeleteMessage) en/fr\n\n**Tests** : 138/138 frontend pass
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
