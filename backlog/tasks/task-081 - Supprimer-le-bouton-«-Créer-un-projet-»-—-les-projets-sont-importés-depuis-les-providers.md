---
id: TASK-081
title: >-
  Supprimer le bouton « Créer un projet » — les projets sont importés depuis les
  providers
status: Done
assignee: []
created_date: '2026-03-18 22:05'
updated_date: '2026-03-18 22:19'
labels:
  - UX
  - catalog
dependencies: []
priority: low
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Le bouton « Créer un projet » sur la page ProjectList n'a pas de sens car les projets sont importés automatiquement depuis les providers Git (GitHub, GitLab). Le remplacer par un lien vers les Fournisseurs ou le supprimer.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le bouton Créer un projet est remplacé ou supprimé sur ProjectList
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Le bouton Créer existait bien sur ProjectList (RouterLink vers catalog-projects-create). Supprimé le bouton, les deux routes create/edit, et la page ProjectForm.vue.
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Analyse de code statique doit passer
- [ ] #6 Deptrac doit passer
<!-- DOD:END -->
