---
id: TASK-104
title: DependencyList — bouton export unique avec choix CSV ou PDF
status: Done
assignee: []
created_date: '2026-03-19 07:42'
updated_date: '2026-03-19 07:48'
labels:
  - feature
  - dependency
  - export
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Même pattern que la page Stacks techniques : remplacer le bouton Export CSV par un dropdown unifié CSV / PDF avec un PDF au design professionnel.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Un seul bouton Exporter avec dropdown CSV / PDF sur DependencyList
- [x] #2 L'export PDF génère un document avec score de santé, gap stats et tableau
- [x] #3 Les filtres actifs sont respectés
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
- Bouton CSV remplacé par ExportDropdown (CSV / PDF)
- Service `dependencyPdfExport.ts` : en-tête Monark, score de santé (barre pleine largeur), gap stats (3 cards), tableau groupé par dépendance avec couleurs (statut, écart, vulns), pied de page paginé
- Groupement par nom de dépendance dans le PDF (même pattern que stacks par projet)
- Les filtres actifs sont respectés (export sur `filteredDeps`)
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
