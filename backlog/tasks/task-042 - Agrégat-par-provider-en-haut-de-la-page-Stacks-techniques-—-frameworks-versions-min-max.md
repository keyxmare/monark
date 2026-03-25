---
id: TASK-042
title: >-
  Agrégat par provider en haut de la page Stacks techniques — frameworks,
  versions min/max
status: Done
assignee: []
created_date: '2026-03-18 20:24'
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
En haut de la page **Stacks techniques**, afficher des cartes d'agrégation par provider montrant un résumé des frameworks utilisés et la plage de versions détectées.

## Affichage attendu
Pour chaque provider (ex: GitHub keyxmare, GitLab motoblouz), une carte contenant :
- **Nom du provider** + type (GitHub/GitLab)
- **Frameworks détectés** : liste des frameworks uniques avec pour chacun :
  - Nom du framework (ex: Symfony, Vue, Nuxt)
  - **Version min** : la plus ancienne version détectée parmi les projets du provider
  - **Version max** : la plus récente version détectée
  - Badge "Non maintenu" si la version min est EOL
- **Nombre de projets** utilisant ce provider

## Exemple visuel
```
┌─ GitHub (keyxmare) — 5 projets ──────────────┐
│ Symfony    1.4.9 → 8.0.3                      │
│ Vue        3.4.0 → 3.5.13                     │
│ Nuxt       3.12.0 → 3.15.0                    │
└───────────────────────────────────────────────┘
```

## Données
- Les tech stacks sont déjà disponibles via l'API avec `project_id`
- Il faut les agréger côté frontend par provider (via le `projectId` → project → provider)
- Ou créer un endpoint backend dédié pour l'agrégation

## Point à décider
- Frontend-only (agrège les données existantes) vs endpoint backend dédié (plus performant pour beaucoup de projets)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Des cartes d'agrégation par provider s'affichent en haut de la page
- [x] #2 Chaque carte liste les frameworks détectés avec version min et max
- [x] #3 Le nombre de projets par provider est affiché
- [x] #4 Le badge Non maintenu s'affiche si la version min est EOL
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Cartes d'agrégation par provider en haut de la page Stacks techniques :

- Pour chaque provider : nom + type (badge) + nombre de projets
- Frameworks détectés avec plage de versions (min → max) ou version unique
- Agrégation calculée côté frontend à partir des stacks + projets + providers déjà chargés
- i18n ajouté : `project`, `versionRange`, `projectCount` (fr + en)
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
