---
id: TASK-041
title: Page Stacks techniques — vue globale avec LTS et badges par projet
status: Done
assignee: []
created_date: '2026-03-18 20:22'
updated_date: '2026-03-18 20:26'
labels:
  - feature
  - catalog
  - DX
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
La page **Stacks techniques** (menu Catalogue > Stacks techniques) doit afficher une vue agrégée de tous les projets avec leurs stacks, versions, écart LTS et badges de maintenance.

## État actuel
La page liste les tech stacks de manière plate (une ligne par stack) sans contexte projet, sans infos LTS ni badges.

## Comportement attendu
Pour chaque projet, afficher ses stacks techniques avec :
- **Projet** : nom du projet (lien vers le détail)
- **Framework** : nom + version installée + badge "Non maintenu" / "Inactif" si applicable
- **Dernière LTS** : version LTS la plus récente (via endoflife.date)
- **Écart LTS** : temps humanisé entre la version installée et la LTS, coloré (vert/orange/rouge)
- **Langage** : nom + version

## Réutilisation
Le composable `useFrameworkLts` (avec aliases, maintenance status, LTS gap) est déjà prêt — il suffit de l'intégrer dans la page TechStackList comme c'est fait dans ProjectDetail.

## Frameworks supportés
Symfony, Vue.js, Nuxt (extensible via `FRAMEWORK_MAP` et `FRAMEWORK_ALIASES`)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Chaque ligne affiche le nom du projet associé à la stack
- [x] #2 La version du framework affiche le badge Non maintenu / Inactif si applicable
- [x] #3 La colonne Dernière LTS affiche la version LTS ou stable la plus récente
- [x] #4 La colonne Écart LTS affiche le temps humanisé avec couleur vert/orange/rouge
- [x] #5 Les frameworks non trackés affichent « — » pour LTS et écart
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Page TechStackList entièrement refaite avec :

- **Colonne Projet** : nom du projet en lien vers le détail
- **Colonne Framework Version** : avec badges "Non maintenu" (rouge) / "Inactif" (orange)
- **Colonne Dernière LTS** : version LTS via endoflife.date
- **Colonne Écart LTS** : temps humanisé avec couleur (vert/orange/rouge) ou "À jour"
- **Pagination** à 100 par page

Réutilisation complète du composable `useFrameworkLts` (aliases, maintenance status, LTS gap).

Ajout de `providerId` au type frontend `Project`.
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
