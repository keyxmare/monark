---
id: TASK-102
title: Stacks techniques — bouton export unique avec choix CSV ou PDF
status: Done
assignee: []
created_date: '2026-03-19 07:17'
updated_date: '2026-03-19 07:24'
labels:
  - feature
  - catalog
  - export
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Remplacer le bouton Export CSV existant et le futur bouton PDF par un **seul bouton d'export** avec un dropdown proposant le choix du format (CSV ou PDF).

## Comportement
- Un bouton « Exporter » avec une flèche dropdown
- Au clic : menu avec deux options « CSV » et « PDF »
- CSV : comportement actuel (téléchargement immédiat)
- PDF : génération d'un document avec design professionnel

## Contenu du PDF
- **En-tête** : logo/nom Monark, date de génération, titre « Rapport Stacks Techniques »
- **Score de santé** : barre visuelle avec % à jour, nombre EOL, nombre inactifs
- **Agrégation par provider** : frameworks et plages de versions (min → max)
- **Tableau détaillé** : projet, framework, version, dernière LTS, écart LTS, statut maintenance
- **Pied de page** : pagination, date de génération

## Design PDF
- Couleurs cohérentes avec le thème Monark
- Badges colorés pour le statut maintenance
- Couleurs d'écart LTS (vert/orange/rouge)
- Mise en page A4 paysage

## Technique
- Composant dropdown réutilisable pour le bouton d'export
- Les filtres actifs doivent être respectés dans les deux formats
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Un seul bouton Exporter avec dropdown CSV / PDF
- [x] #2 L'export CSV fonctionne comme avant
- [x] #3 L'export PDF génère un document avec design professionnel
- [x] #4 Les filtres actifs sont respectés dans les deux formats
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
- Composant `ExportDropdown` réutilisable avec menu CSV / PDF
- Service `techStackPdfExport.ts` avec jsPDF + autoTable : en-tête Monark, score de santé (barre), agrégation providers, tableau détaillé avec couleurs statut/écart, pied de page paginé
- Intégré dans TechStackList en remplacement du bouton CSV seul
- Libs installées : jspdf + jspdf-autotable
- i18n : clé `common.actions.export` ajoutée (fr + en)
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
