---
id: TASK-068
title: DependencyForm — autocomplete projet au lieu de text input UUID
status: Done
assignee: []
created_date: '2026-03-18 21:48'
updated_date: '2026-03-18 22:03'
labels:
  - UX
  - dependency
dependencies: []
priority: low
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Le champ projectId en mode création est un simple text input où l'utilisateur doit copier-coller un UUID. Le remplacer par un select ou autocomplete avec les noms de projets.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le champ projectId est un select ou autocomplete avec les noms de projets
- [x] #2 La liste des projets est chargée depuis le store
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
