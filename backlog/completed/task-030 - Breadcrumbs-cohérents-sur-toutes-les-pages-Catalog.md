---
id: TASK-030
title: Breadcrumbs cohérents sur toutes les pages Catalog
status: Done
assignee: []
created_date: '2026-03-13 13:16'
updated_date: '2026-03-13 14:38'
labels:
  - frontend
  - ux
  - cohérence
dependencies: []
references:
  - frontend/src/catalog/pages/ProjectDetail.vue
  - frontend/src/catalog/pages/ProjectForm.vue
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Ajouter des breadcrumbs sur les pages qui n'en ont pas pour assurer la cohérence avec ProjectDetail et ProjectForm qui en ont déjà.

Pages à modifier :
- **ProviderList** : `Providers`
- **ProviderDetail** : `Providers / [Name]` (remplacer le lien "← Back")
- **ProviderForm** : `Providers / Create` ou `Providers / [Name] / Edit`
- **ProjectList** : `Projects`
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 ProviderList affiche un breadcrumb 'Providers'
- [x] #2 ProviderDetail affiche 'Providers / [Name]' avec lien vers la liste
- [x] #3 ProviderForm affiche 'Providers / Create' ou 'Providers / [Name] / Edit'
- [x] #4 ProjectList affiche un breadcrumb 'Projects'
- [x] #5 Style identique aux breadcrumbs existants de ProjectDetail/ProjectForm
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Breadcrumbs ajoutés sur ProjectList (`Projects`), ProviderList (`Providers`), ProviderDetail (`Providers / [Name]`). ProviderForm avait déjà un breadcrumb. Style identique à ProjectDetail/ProjectForm.
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
