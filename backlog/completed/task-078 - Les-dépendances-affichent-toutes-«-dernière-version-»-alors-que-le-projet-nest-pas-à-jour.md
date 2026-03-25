---
id: TASK-078
title: >-
  Les dépendances affichent toutes « dernière version » alors que le projet
  n'est pas à jour
status: Done
assignee: []
created_date: '2026-03-18 21:54'
updated_date: '2026-03-18 23:02'
labels:
  - bug
  - dependency
  - scanner
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Sur le projet front-client par exemple, toutes les dépendances affichent `latestVersion` identique à `currentVersion` et le statut « À jour », alors que le projet a clairement des dépendances obsolètes.\n\nLe problème vient probablement du scanner qui ne récupère pas la `latestVersion` depuis un registre externe (Packagist, npm). Il se contente de mettre la même version que `currentVersion`.\n\nLe fix : lors du scan, interroger les registres (npm registry, Packagist API) pour récupérer la vraie dernière version disponible et détecter les dépendances outdated.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le scanner récupère la dernière version disponible depuis le registre (npm, Packagist)
- [x] #2 Le champ latestVersion reflète la vraie dernière version du registre
- [x] #3 Le champ isOutdated est calculé correctement (currentVersion vs latestVersion)
- [x] #4 Les dépendances réellement obsolètes sont marquées comme telles
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Résolu par TASK-088 + TASK-089 : les adaptateurs registres (npm, Packagist) récupèrent la vraie dernière version, et le handler SyncDependencyVersions met à jour latestVersion et isOutdated sur chaque dépendance.
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
