---
id: TASK-059
title: Enrichir les versions JS/TS et PHP depuis les lock files (versions exactes)
status: Done
assignee: []
created_date: '2026-03-18 21:01'
updated_date: '2026-03-18 21:05'
labels:
  - bug
  - catalog
  - scanner
  - DX
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Les versions de framework affichées sont les **constraints** des manifestes (`package.json`, `composer.json`) au lieu des **versions exactes** installées depuis les lock files.

## Problème actuel

| Écosystème | Source actuelle | Version affichée | Version réelle |
|---|---|---|---|
| **JS/TS** | `package.json` | `3.5.0` (constraint `^3.5.0`) | `3.5.13` (pnpm-lock.yaml) |
| **PHP** (deps) | `composer.lock` ✅ | `8.0.3` (exacte) | OK |
| **PHP** (stack framework) | `composer.lock` ✅ partiellement | `2.8.*` (constraint !) | `2.8.52` (lock) |

→ **JS/TS** : aucun enrichissement lock file
→ **PHP stack** : l'enrichissement framework ne fonctionne pas correctement pour tous les cas — un projet Symfony v3 affiche `2.8.*` (constraint du composer.json) au lieu de la version exacte du lock. Le bug est probablement dans `enrichPhpStackVersions()` qui ne matche que les packages listés dans `FRAMEWORK_PACKAGES` (ex: `symfony/framework-bundle`) mais le projet utilise peut-être un autre package symfony.

## Lock files à supporter (JS/TS)
- `pnpm-lock.yaml` (pnpm) — format YAML, versions dans `importers.*.dependencies.{pkg}.version`
- `package-lock.json` (npm) — format JSON, versions dans `packages.node_modules/{pkg}.version`
- `yarn.lock` (yarn) — format texte custom

## Pattern de référence (PHP)
Le scanner fait déjà :
1. `extractComposerDeps()` → lit les constraints depuis `composer.json`
2. `enrichComposerVersions()` → remplace par les versions exactes de `composer.lock`
3. `enrichPhpStackVersions()` → enrichit la version du framework dans la stack

## Implémentation attendue
### JS/TS
1. Après lecture de `package.json`, chercher le lock file (`pnpm-lock.yaml` > `package-lock.json` > `yarn.lock`)
2. `enrichNpmVersions(dependencies, lockData)` → remplace constraints par versions exactes
3. `enrichJsStackVersions(stacks, lockData)` → enrichit la `frameworkVersion` de la stack

### PHP (fix)
1. Investiguer pourquoi `enrichPhpStackVersions()` ne remplace pas `2.8.*` par la version exacte du lock
2. Vérifier que le fallback `symfony/*` prefix est aussi enrichi, pas seulement `symfony/framework-bundle`

## Impact
- L'écart LTS sera calculé sur la version réellement installée, pas la constraint
- Les badges Non maintenu seront plus précis
- Les agrégats min/max par provider seront exacts
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Les versions JS/TS affichées correspondent aux versions exactes des lock files
- [x] #2 Support de pnpm-lock.yaml (prioritaire)
- [x] #3 Support de package-lock.json (npm)
- [x] #4 Le framework version dans les stacks JS/TS est enrichi depuis le lock file
- [x] #5 Les dépendances npm affichent la version exacte, pas la constraint
- [x] #6 Les versions PHP framework sont enrichies même pour le fallback symfony/* prefix
- [x] #7 Un projet Symfony v3 avec constraint 2.8.* affiche la version exacte du lock
- [x] #8 Les tests couvrent l'enrichissement npm et le fix PHP
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
**Fix PHP** : `enrichPhpStackVersions()` ne trouvait la version lock que pour `symfony/framework-bundle`. Ajout d'un fallback : si le package principal n'est pas dans le lock, cherche le premier `symfony/*` ou `laravel/*` pour enrichir la frameworkVersion. Résout le cas Symfony v3 avec `2.8.*` → `2.8.52`.

**JS/TS enrichissement complet** :
- `resolveNpmLockVersions()` : cherche `pnpm-lock.yaml` > `package-lock.json` (priorité pnpm)
- `parsePnpmLock()` : parse le format YAML de pnpm via regex pour extraire les versions exactes
- `parseNpmLock()` : parse le format JSON de npm v7+ (`packages.node_modules/{pkg}.version`)
- `enrichNpmVersions()` : remplace les constraints par les versions exactes (pattern identique à `enrichComposerVersions`)
- `enrichJsStackVersions()` : enrichit `frameworkVersion` + version TypeScript depuis le lock (pattern identique à `enrichPhpStackVersions`)
- `JS_FRAMEWORK_PACKAGES` : mapping framework → package npm (Vue, Nuxt, React, Next.js, Angular, Svelte, Astro, Remix)

**3 tests ajoutés** :
- pnpm-lock.yaml enrichit version framework Vue + TypeScript + dépendances
- package-lock.json enrichit version framework React + dépendances  
- PHP symfony prefix fallback enrichit `2.8.*` → `2.8.52` depuis le lock
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
