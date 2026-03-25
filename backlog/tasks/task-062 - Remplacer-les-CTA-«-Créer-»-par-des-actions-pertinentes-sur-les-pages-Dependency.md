---
id: TASK-062
title: >-
  Remplacer les CTA « Créer » par des actions pertinentes sur les pages
  Dependency
status: Done
assignee: []
created_date: '2026-03-18 21:48'
updated_date: '2026-03-18 21:52'
labels:
  - UX
  - dependency
dependencies: []
priority: low
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Les boutons « Créer une dépendance » et « Créer une vulnérabilité » sont trompeurs car ces entités sont détectées par scan, pas créées manuellement. Les remplacer ou supprimer.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le bouton Créer est remplacé ou supprimé sur DependencyList
- [x] #2 Le bouton Créer est remplacé ou supprimé sur VulnerabilityList
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
