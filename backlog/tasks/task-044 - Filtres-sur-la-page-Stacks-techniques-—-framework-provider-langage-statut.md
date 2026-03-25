---
id: TASK-044
title: 'Filtres sur la page Stacks techniques — framework, provider, langage, statut'
status: Done
assignee: []
created_date: '2026-03-18 20:33'
updated_date: '2026-03-18 20:37'
labels:
  - feature
  - catalog
  - UX
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
La page Stacks techniques n'a aucun filtre alors que Projets et Fournisseurs ont recherche + filtres. Ajouter :
- Recherche textuelle (par nom de projet ou framework)
- Filtre par framework (Symfony, Vue, Nuxt…)
- Filtre par provider
- Filtre par langage (PHP, TypeScript…)
- Filtre par statut de maintenance (Tous / À jour / Non maintenu / Inactif)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Un champ de recherche filtre par nom de projet ou framework
- [x] #2 Un filtre par framework est disponible
- [x] #3 Un filtre par provider est disponible
- [x] #4 Un filtre par statut de maintenance est disponible
- [x] #5 Les filtres sont cohérents visuellement avec ceux des pages Projets et Fournisseurs
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Analyse de code statique doit passer
- [ ] #6 Deptrac doit passer
<!-- DOD:END -->
