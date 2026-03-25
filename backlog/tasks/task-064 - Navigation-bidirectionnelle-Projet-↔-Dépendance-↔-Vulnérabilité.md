---
id: TASK-064
title: Navigation bidirectionnelle Projet ↔ Dépendance ↔ Vulnérabilité
status: Done
assignee: []
created_date: '2026-03-18 21:48'
updated_date: '2026-03-18 21:56'
labels:
  - UX
  - dependency
  - catalog
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Actuellement la navigation est cassée :\n- ProjectDetail → onglet dépendances (read-only, pas de lien vers DependencyDetail)\n- DependencyDetail → pas de lien vers ProjectDetail\n- VulnerabilityDetail → lien vers Dependency ✅ mais pas vers Project\n\nAjouter les liens manquants pour naviguer dans les deux sens.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 ProjectDetail onglet dépendances : chaque dépendance est cliquable vers DependencyDetail
- [x] #2 DependencyDetail affiche un lien vers le projet parent
- [x] #3 VulnerabilityDetail affiche un lien vers le projet via la dépendance
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
