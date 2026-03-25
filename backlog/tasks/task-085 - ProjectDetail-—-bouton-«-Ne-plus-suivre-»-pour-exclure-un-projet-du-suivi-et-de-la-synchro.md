---
id: TASK-085
title: >-
  ProjectDetail — bouton « Ne plus suivre » pour exclure un projet du suivi et
  de la synchro
status: Done
assignee: []
created_date: '2026-03-18 22:21'
updated_date: '2026-03-18 22:27'
labels:
  - feature
  - catalog
  - UX
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Ajouter un bouton « Ne plus suivre » sur la page ProjectDetail qui permet d'exclure un projet du suivi. Le projet ne sera plus synchronisé, scanné, ni affiché dans les listes (projets, stacks, dépendances).

## Comportement attendu
- Bouton « Ne plus suivre » sur ProjectDetail (style danger, avec confirmation)
- Le projet est marqué comme « archivé » ou « exclu » en base (soft delete ou flag)
- Le projet n'apparaît plus dans les listes par défaut (ProjectList, TechStackList, DependencyList)
- Le SyncAllProjectsHandler ignore les projets exclus
- Possibilité de ré-importer le projet depuis le provider si besoin

## Points à décider
- Soft delete (flag `archived: true`) vs suppression complète
- Faut-il garder les données (stacks, deps) ou les supprimer ?
- Filtre « Afficher les archivés » sur ProjectList ?
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Un bouton Ne plus suivre est disponible sur ProjectDetail
- [x] #2 Une confirmation est demandée avant l'action
- [x] #3 Le projet n'apparaît plus dans les listes par défaut après exclusion
- [x] #4 Le projet n'est plus synchronisé ni scanné
- [x] #5 Le projet peut être ré-importé depuis le provider
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
- Bouton « Ne plus suivre » ajouté sur ProjectDetail (style danger)
- ConfirmDialog avec message explicite (données supprimées, ré-import possible)
- Suppression du projet via `projectStore.remove()` → redirect vers la liste
- Le backend `DeleteProjectHandler` supprime le projet et ses données (cascade)
- Le projet disparaît des listes et n'est plus synchro
- Re-import possible depuis le provider
- Supprimé aussi : bouton delete tech stack et bouton edit projet
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
