---
id: TASK-060
title: DependencyList — afficher le projet parent avec lien
status: Done
assignee: []
created_date: '2026-03-18 21:48'
updated_date: '2026-03-18 21:50'
labels:
  - UX
  - dependency
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Le `projectId` existe dans les données mais n'est jamais affiché. Ajouter une colonne Projet avec le nom du projet en lien vers ProjectDetail, comme sur la page Stacks techniques.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Une colonne Projet affiche le nom du projet en lien cliquable
- [x] #2 Le lien mène vers la page détail du projet
- [x] #3 DependencyDetail affiche aussi le projet parent en lien
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
