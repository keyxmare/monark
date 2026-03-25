---
id: TASK-033
title: >-
  Remplacer techStacksCount par logos frameworks dans ProjectDetail et
  ProjectList
status: Done
assignee: []
created_date: '2026-03-13 16:20'
updated_date: '2026-03-13 16:25'
labels:
  - frontend
  - backend
  - ux
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Remplacer le compteur "Tech Stacks: N" par des badges visuels avec les noms/logos des langages et frameworks détectés.

**Backend** : Enrichir `ProjectOutput` pour inclure un résumé des tech stacks (language + framework) au lieu du simple count.

**Frontend** :
- Créer un composant `TechBadge.vue` (dot coloré + nom) avec mapping de couleurs par technologie (comme GitHub linguist)
- ProjectDetail : remplacer la stats card count par une rangée de badges
- ProjectList : remplacer `techStacksCount` par les badges dans chaque card
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Backend: ProjectOutput inclut un tableau techStacks avec language et framework
- [x] #2 Composant TechBadge avec dot coloré par technologie
- [x] #3 ProjectDetail: stats card affiche les badges au lieu du count
- [x] #4 ProjectList: cards affichent les badges au lieu du count
- [x] #5 Tests backend et frontend passent
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Backend enrichi avec TechStackSummaryDTO dans ProjectOutput. TechBadge.vue avec 23 couleurs style GitHub linguist. ProjectDetail affiche les badges dédupliqués (language+framework), ProjectList montre max 5 badges avec +N. 438 tests backend + 138 frontend passent.
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
