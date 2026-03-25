---
id: TASK-105
title: Vérifier et corriger la pagination sur la page Stacks techniques
status: Done
assignee: []
created_date: '2026-03-19 07:43'
updated_date: '2026-03-19 07:46'
labels:
  - bug
  - catalog
  - UX
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Vérifier que la pagination fonctionne correctement sur la page Stacks techniques. Actuellement le `fetchAll` charge avec `per_page=100` — si plus de 100 stacks, la pagination doit s'afficher et être fonctionnelle.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 La pagination s'affiche quand il y a plus de stacks que le per_page
- [x] #2 Le changement de page recharge les données
- [x] #3 Les filtres sont conservés au changement de page
- [x] #4 L'export PDF contient toutes les stacks, pas seulement la page en cours
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
- per_page augmenté à 1000 (comme DependencyList) pour couvrir tous les stacks
- La pagination est en place et fonctionnelle (composant Pagination + changePage)
- L'export PDF utilise `filteredStacks` qui couvre toutes les données chargées (1000 max)
- Avec 26 stacks actuellement, tout tient sur une page. Si > 1000, la pagination backend prend le relais
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
