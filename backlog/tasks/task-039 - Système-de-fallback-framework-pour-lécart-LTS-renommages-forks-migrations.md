---
id: TASK-039
title: 'Système de fallback framework pour l''écart LTS (renommages, forks, migrations)'
status: Done
assignee: []
created_date: '2026-03-18 20:09'
updated_date: '2026-03-18 20:16'
labels:
  - feature
  - catalog
  - scanner
  - DX
dependencies: []
priority: low
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Quand un framework a changé de nom/repo au fil du temps (ex: `symfony/symfony1` → `symfony/framework-bundle`), le calcul d'écart LTS doit pouvoir résoudre l'ancien nom vers le nouveau pour comparer correctement.

## Problème actuel
Le projet motoblouz-v2 est sur Symfony 1.4.9. Le composable `useFrameworkLts` compare avec la LTS Symfony 7.4 et affiche "12 ans 11 mois" — le fallback sur le plus ancien cycle fonctionne. Mais c'est un comportement accidentel, pas une feature explicite.

## Solution attendue
Un système de **framework aliases** configurable et réutilisable dans le composable `useFrameworkLts` :

```ts
const FRAMEWORK_ALIASES: Record<string, string> = {
  'Symfony1': 'Symfony',
  // futurs cas : 'AngularJS' → 'Angular', etc.
}
```

- Quand le scanner détecte un framework ancien (ex: via `symfony/symfony1`), il le nomme `Symfony1` (ou similaire)
- Le composable LTS résout l'alias vers le framework actuel pour la comparaison
- Le mapping est déclaratif et extensible (pas de if/else en dur)

## Périmètre
- **Scanner backend** : détecter `symfony/symfony1` comme framework distinct (ex: `Symfony1`) au lieu de `Symfony`
- **Composable frontend** : table d'aliases pour résoudre les anciens noms vers les noms actuels dans endoflife.date
- **Extensible** : ajouter un nouveau fallback = une ligne dans la table d'aliases
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Un système d'aliases framework est en place (déclaratif, pas de if/else)
- [x] #2 Symfony 1.x est résolu vers Symfony pour la comparaison LTS
- [x] #3 Ajouter un nouveau fallback = une ligne dans la config
- [x] #4 Les tests couvrent le mécanisme de résolution d'alias
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Système d'aliases déclaratif dans `useFrameworkLts.ts` :

- `FRAMEWORK_ALIASES` : table `Record<string, string>` — une ligne par mapping (ex: `Symfony1 → Symfony`, `AngularJS → Angular`)
- `resolveFramework()` : fonction exportée qui résout les alias avant lookup endoflife.date
- Toutes les fonctions du composable (`getLtsInfo`, `getVersionReleaseDate`, `getVersionMaintenanceStatus`, `loadForFrameworks`) passent par `resolveFramework`
- 4 tests unitaires pour le mécanisme de résolution

Ajouter un nouveau fallback = une ligne dans `FRAMEWORK_ALIASES`.
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
