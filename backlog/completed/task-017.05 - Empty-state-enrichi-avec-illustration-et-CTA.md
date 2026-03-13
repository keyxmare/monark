---
id: TASK-017.05
title: Empty state enrichi avec illustration et CTA
status: Done
assignee: []
created_date: '2026-03-12 16:10'
updated_date: '2026-03-12 16:25'
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
Item #5 — L'empty state "Aucun fournisseur" est un simple texte centré. Ajouter une illustration (ou icône SVG), un message descriptif et un bouton CTA "Ajouter un fournisseur" qui redirige vers le formulaire de création.

## Pages impactées
- ProviderList.vue → empty state quand aucun provider
- ProviderDetail.vue → empty state quand aucun remote project
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Icône ou illustration visible dans l'empty state
- [x] #2 Message descriptif expliquant quoi faire
- [x] #3 Bouton CTA vers le formulaire de création (ProviderList)
- [x] #4 Message adapté pour les remote projects vides (ProviderDetail)
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
