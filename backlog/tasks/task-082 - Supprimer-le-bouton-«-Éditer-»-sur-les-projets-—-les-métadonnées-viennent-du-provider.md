---
id: TASK-082
title: >-
  Supprimer le bouton « Éditer » sur les projets — les métadonnées viennent du
  provider
status: Done
assignee: []
created_date: '2026-03-18 22:07'
updated_date: '2026-03-18 22:19'
labels:
  - UX
  - catalog
dependencies: []
priority: low
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Le bouton « Éditer » sur ProjectDetail et ProjectList n'a pas de sens car les métadonnées des projets (nom, description, branche, visibilité) sont synchronisées automatiquement depuis les providers Git. Permettre l'édition manuelle crée une incohérence avec la source de vérité (le provider).

Supprimer aussi la page ProjectForm (edit) et la route associée.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le bouton Éditer est retiré de ProjectDetail
- [x] #2 L'action Éditer est retirée du dropdown de ProjectList
- [x] #3 La page ProjectForm (edit) et sa route sont supprimées
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
