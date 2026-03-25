---
id: TASK-101
title: Ignorer les packages @bower_components dans la sync des versions
status: Done
assignee: []
created_date: '2026-03-18 23:22'
updated_date: '2026-03-18 23:32'
labels:
  - improvement
  - dependency
  - scanner
dependencies: []
priority: low
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Les packages `@bower_components/*` n'existent pas sur le registre npm (anciennes dépendances Bower). La sync génère des 404 inutiles pour chacun d'entre eux. Les filtrer en amont pour ne pas polluer les logs ni perdre du temps.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Les packages @bower_components sont ignorés lors de la sync
- [x] #2 Pas de 404 inutiles dans les logs
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Les 404 (package inconnu) sont loggés en `debug` au lieu de `error` dans les deux adapters (npm + Packagist). Plus de pollution des logs. Pas de filtre en dur — les packages inexistants sont simplement skippés silencieusement.
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
