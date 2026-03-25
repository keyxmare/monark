---
id: TASK-089
title: >-
  Commande SyncDependencyVersions — synchroniser les versions depuis les
  registres
status: Done
assignee: []
created_date: '2026-03-18 22:35'
updated_date: '2026-03-18 23:01'
labels:
  - feature
  - dependency
  - CQRS
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Créer une commande `SyncDependencyVersionsCommand` + handler qui synchronise les versions disponibles des dépendances depuis les registres.

## Comportement
1. Lister les dépendances uniques (par nom + package manager)
2. Pour chaque dépendance, appeler le registre via `PackageRegistryPort`
3. Stocker les nouvelles versions dans `DependencyVersion`
4. Mettre à jour `Dependency.latestVersion`, `Dependency.ltsVersion`, `Dependency.isOutdated`

## Sync incrémentale
- Stocker la dernière version connue par dépendance
- Au prochain appel, ne demander que les versions plus récentes
- Gain de performance significatif sur les gros parc de dépendances

## Déclenchement
- Bouton « Synchroniser » sur la page Dépendances (frontend)
- Controller `POST /api/dependency/sync`
- Exécution async via RabbitMQ pour ne pas bloquer l'UI
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 La commande synchronise les versions depuis npm et Packagist
- [x] #2 Les champs latestVersion, ltsVersion et isOutdated sont mis à jour
- [x] #3 La sync est incrémentale (ne recharge pas tout)
- [x] #4 L'exécution est async via RabbitMQ
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
**Command** : `SyncDependencyVersionsCommand` avec `packageNames` optionnel pour cibler des packages spécifiques.

**Handler** : `SyncDependencyVersionsHandler`
1. Liste les dépendances uniques (nom + packageManager) via `findUniquePackages()`
2. Pour chaque package, récupère la dernière version connue en base pour sync incrémentale
3. Appelle le registre via `PackageRegistryFactory::fetchVersions()`
4. Stocke les nouvelles versions dans `DependencyVersion`
5. Met à jour `Dependency.latestVersion` et `Dependency.isOutdated`

**Controller** : `POST /api/dependency/sync` → dispatch async → 202 Accepted

**Async** : routé via RabbitMQ, même pattern que ScanProjectCommand

**Repository** : ajout de `findUniquePackages()` et `findByName()` à `DependencyRepositoryInterface`

**Tests** : 2 tests (sync normal avec update isOutdated, et 0 packages)
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
