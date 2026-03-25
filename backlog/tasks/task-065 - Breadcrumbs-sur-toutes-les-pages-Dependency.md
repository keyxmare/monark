---
id: TASK-065
title: Breadcrumbs sur toutes les pages Dependency
status: Done
assignee: []
created_date: '2026-03-18 21:48'
updated_date: '2026-03-18 21:58'
labels:
  - ui
  - dependency
  - consistency
dependencies: []
priority: low
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Aucune page du contexte Dependency n'a de breadcrumb alors que toutes les pages Catalog en ont. Ajouter des breadcrumbs cohérents sur DependencyList, DependencyDetail, DependencyForm, VulnerabilityList, VulnerabilityDetail, VulnerabilityForm.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Chaque page Dependency a un breadcrumb
- [x] #2 Le style est cohérent avec les pages Catalog
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
