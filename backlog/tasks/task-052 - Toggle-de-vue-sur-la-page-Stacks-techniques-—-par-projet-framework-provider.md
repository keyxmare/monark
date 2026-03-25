---
id: TASK-052
title: >-
  Toggle de vue sur la page Stacks techniques — par projet / framework /
  provider
status: Done
assignee: []
created_date: '2026-03-18 20:33'
updated_date: '2026-03-18 20:46'
labels:
  - feature
  - catalog
  - UX
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Actuellement le tableau est groupé uniquement par projet. Ajouter un toggle de vue permettant de grouper par :
- **Par projet** (actuel) : chaque projet avec ses stacks
- **Par framework** : chaque framework avec la liste des projets qui l'utilisent et leurs versions
- **Par provider** : chaque provider avec ses projets et stacks

Permet de répondre à des questions différentes : « quels projets utilisent Symfony ? » vs « quel est l'état des stacks du provider GitHub ? »
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Un toggle permet de basculer entre 3 vues : par projet, par framework, par provider
- [x] #2 La vue par framework liste les projets par framework avec leurs versions
- [x] #3 La vue par provider regroupe par provider puis par projet
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
