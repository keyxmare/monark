---
id: TASK-031
title: Redesign ProviderDetail — stats cards + pagination + toast
status: Done
assignee: []
created_date: '2026-03-13 13:16'
updated_date: '2026-03-13 14:44'
labels:
  - frontend
  - ux
  - cohérence
dependencies:
  - TASK-028
references:
  - frontend/src/catalog/pages/ProviderDetail.vue
  - frontend/src/catalog/pages/ProjectDetail.vue
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Aligner le design de ProviderDetail sur le standard établi par ProjectDetail.

**Stats cards** : remplacer les `dl/dt/dd` pour les health stats par des stats cards visuelles (status, projectsCount, syncFreshness, apiLatency) comme sur ProjectDetail.

**Pagination remote projects** : le store et le service supportent la pagination mais la page n'affiche pas de contrôles. Ajouter le composant `<Pagination>`.

**Toast après import** : afficher un toast de confirmation après l'import de projets (comme le scan project).

**ConfirmDialog delete** : s'assurer que la suppression du provider passe par ConfirmDialog.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Health stats affichées en stats cards (même style que ProjectDetail)
- [x] #2 Pagination visible sur la section remote projects
- [x] #3 Toast de confirmation après import de projets réussi
- [x] #4 Suppression provider via ConfirmDialog
- [x] #5 Composant <Pagination> partagé utilisé
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
ProviderDetail splitté en 4 composants (217 + 182 + 71 + 375 lignes). Stats cards, pagination remote projects, toast import et ConfirmDialog delete étaient déjà en place. Ajout toast importSuccess.
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
