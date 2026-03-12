---
id: TASK-017.06
title: 'Icônes provider (GitLab, GitHub, Bitbucket)'
status: Done
assignee: []
created_date: '2026-03-12 16:10'
updated_date: '2026-03-12 16:29'
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
Item #6 — Remplacer le badge texte `gitlab`/`github`/`bitbucket` par l'icône/logo du provider. Plus reconnaissable d'un coup d'œil.

## Approche
Utiliser des icônes SVG inline (ou un icon set comme Simple Icons). Afficher l'icône + le nom dans le badge, ou juste l'icône si l'espace est contraint (avec tooltip).

## Pages impactées
- ProviderList.vue → colonne Type
- ProviderDetail.vue → champ Type dans la fiche
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Icône SVG pour GitLab, GitHub et Bitbucket
- [x] #2 Icône visible dans ProviderList et ProviderDetail
- [x] #3 Couleurs cohérentes avec la charte de chaque provider
- [x] #4 Tooltip avec le nom du provider si icône seule
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
