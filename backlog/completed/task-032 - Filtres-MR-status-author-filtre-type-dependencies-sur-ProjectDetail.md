---
id: TASK-032
title: Filtres MR (status/author) + filtre type dependencies sur ProjectDetail
status: Done
assignee: []
created_date: '2026-03-13 13:16'
updated_date: '2026-03-13 14:44'
labels:
  - frontend
  - ux
dependencies: []
references:
  - frontend/src/catalog/pages/ProjectDetail.vue
  - frontend/src/catalog/services/merge-request.service.ts
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Enrichir les onglets de ProjectDetail avec des filtres supplémentaires.

**Onglet Merge Requests** :
- Filtre par status (all/open/draft/merged/closed) — le service `MergeRequestService.list()` supporte déjà `status` et `author`
- Filtre par author (text input)

**Onglet Dependencies** :
- Ajouter un filtre par type (all/runtime/dev) en plus du filtre packageManager existant
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Filtre select par status MR (all/open/draft/merged/closed)
- [x] #2 Filtre text input par author MR
- [x] #3 Filtre select par type dependency (all/runtime/dev)
- [x] #4 Les filtres se combinent avec les filtres existants
- [x] #5 État vide adapté quand aucun résultat
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Filtres MR (status select + author search) et filtre type deps (all/runtime/dev) ajoutés sur ProjectDetail. Client-side, cohérent avec les autres filtres. ProjectDetail à 689 lignes — split par onglets recommandé dans une prochaine tâche.
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
