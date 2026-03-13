---
id: TASK-017.07
title: URL provider cliquable avec ouverture nouvel onglet
status: Done
assignee: []
created_date: '2026-03-12 16:10'
updated_date: '2026-03-12 16:30'
labels:
  - frontend
  - ui/ux
  - catalog
dependencies: []
parent_task_id: TASK-017
priority: low
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Item #7 — L'URL du provider est affichée en texte brut. La transformer en lien `<a>` cliquable ouvrant dans un nouvel onglet (`target="_blank" rel="noopener"`).

## Pages impactées
- ProviderList.vue → colonne URL
- ProviderDetail.vue → champ URL dans la fiche
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 URL cliquable sur ProviderList et ProviderDetail
- [x] #2 Ouverture dans un nouvel onglet
- [x] #3 Attribut rel="noopener" présent
- [x] #4 Style visuel de lien (underline ou couleur primary)
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
