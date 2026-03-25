---
id: TASK-103
title: >-
  Cards statistiques écart LTS — cumulé, moyenne et médiane par projet (stacks)
  et par dépendance (deps)
status: Done
assignee: []
created_date: '2026-03-19 07:24'
updated_date: '2026-03-19 07:42'
labels:
  - feature
  - catalog
  - dependency
  - DX
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Ajouter des cards visuelles sur les pages Stacks techniques et Dépendances montrant des statistiques d'écart LTS.

## Stacks techniques (par projet)
- **Écart cumulé** : somme des écarts LTS de toutes les stacks
- **Moyenne par projet** : écart moyen par projet
- **Médiane** : valeur médiane de l'écart par projet

## Dépendances (par dépendance)
- **Écart cumulé** : somme des écarts max par dépendance (prendre le max de l'écart pour chaque dépendance unique, pas par projet)
- **Moyenne par dépendance** : écart moyen
- **Médiane** : valeur médiane

## Affichage
- 3 cards en ligne sous le score de santé
- Format humanisé (jours, mois, ans)
- Couleur selon la valeur (vert/orange/rouge)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 3 cards s'affichent sur la page Stacks techniques (cumulé, moyenne, médiane par projet)
- [x] #2 3 cards s'affichent sur la page Dépendances (cumulé, moyenne, médiane par dépendance)
- [x] #3 Pour les dépendances, le cumulé utilise le max de l'écart par dépendance unique
- [x] #4 Les valeurs sont en format humanisé avec couleur
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
**Stacks techniques** : 3 cards (cumulé, moyenne, médiane) calculées sur les écarts LTS des stacks filtrées. Couleur vert/orange/rouge selon la valeur. Intégrées aussi dans le PDF.

**Dépendances** : 3 cards calculées par dépendance unique (max de l'écart pour chaque dep). Utilise `currentVersionReleasedAt` et `latestVersionReleasedAt` du backend.

Fonctions utilitaires : `humanizeMs()` et `msUrgency()` ajoutées au composable `useFrameworkLts`.
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
