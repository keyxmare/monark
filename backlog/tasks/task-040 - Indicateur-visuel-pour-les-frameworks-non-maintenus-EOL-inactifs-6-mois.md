---
id: TASK-040
title: Indicateur visuel pour les frameworks non maintenus (EOL / inactifs > 6 mois)
status: Done
assignee: []
created_date: '2026-03-18 20:11'
updated_date: '2026-03-18 20:16'
labels:
  - feature
  - catalog
  - DX
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Ajouter un indicateur visuel à côté de la version du framework dans l'onglet Stacks techniques quand le framework n'est plus maintenu.

## Critères d'inactivité
Un framework est considéré non maintenu si :
- Son champ `eol` sur endoflife.date est une date passée (fin de support officielle)
- OU sa `latestReleaseDate` date de plus de 6 mois (pas de release récente)

## Affichage attendu
- Badge/icône à côté de la version du framework (ex: badge "EOL" rouge, ou icône warning)
- Tooltip ou texte explicatif : "Fin de support : {date}" ou "Dernière release il y a {durée}"
- Ne s'affiche que pour les frameworks trackés (Symfony, Vue, Nuxt)

## Données
Les données sont déjà disponibles via endoflife.date (champs `eol`, `latestReleaseDate` par cycle). Le composable `useFrameworkLts` stocke déjà les cycles — il suffit de chercher le cycle correspondant à la version installée et vérifier son statut EOL.

## Exemples concrets
- Symfony 1.4 → EOL depuis 2012 → badge "EOL" rouge
- Symfony 6.4 → support actif jusqu'en 2027 → pas de badge
- Vue 2 → EOL depuis 2023-12-31 → badge "EOL" rouge
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Un badge EOL rouge s'affiche à côté de la version pour les frameworks en fin de vie
- [x] #2 Un badge warning s'affiche pour les frameworks sans release depuis 6+ mois
- [x] #3 Les frameworks activement maintenus n'ont aucun badge
- [x] #4 Le statut est déterminé via les données endoflife.date déjà cachées dans useFrameworkLts
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Badge de maintenance à côté de la version framework dans le tableau tech stacks :

- **Badge "EOL" rouge** : quand `eol` est une date passée ou `true` (Symfony 1.4, Vue 2, etc.)
- **Badge "Inactif" orange** : quand la dernière release date de plus de 6 mois mais le framework n'est pas officiellement EOL
- **Pas de badge** : framework activement maintenu
- Tooltip avec détail (date EOL ou durée depuis dernière release)

Fonction `getMaintenanceStatus()` exportée et testée (5 tests) :
- EOL par date passée
- EOL par booléen true
- Warning par inactivité > 6 mois
- Active avec EOL futur
- Active avec EOL booléen false

i18n ajouté : `eol`, `eolSince`, `inactive`, `inactiveSince` (fr + en)
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
