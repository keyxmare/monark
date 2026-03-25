---
id: TASK-087
title: >-
  Nouvelle entité DependencyVersion — historique des versions depuis les
  registres
status: Done
assignee: []
created_date: '2026-03-18 22:35'
updated_date: '2026-03-18 22:51'
labels:
  - feature
  - dependency
  - DDD
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Créer une entité `DependencyVersion` dans le bounded context Dependency pour stocker les versions disponibles d'une dépendance, récupérées depuis les registres (npm, Packagist, PyPI).

## Champs
- `id` (UUID)
- `dependencyName` (string) — nom du package (ex: `symfony/framework-bundle`)
- `packageManager` (enum: composer, npm, pip)
- `version` (string) — numéro de version (ex: `8.0.3`)
- `releaseDate` (datetime) — date de publication
- `isLts` (bool) — si c'est une version LTS
- `isLatest` (bool) — si c'est la dernière version stable
- `createdAt` (datetime)

## Relations
- Pas de relation directe avec `Dependency` pour éviter le couplage — lien via `dependencyName` + `packageManager`

## Index
- Unique sur `(dependencyName, packageManager, version)`
- Index sur `(dependencyName, packageManager)` pour les requêtes de listing

## Migration
- Créer la table `dependency_versions`
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 L'entité DependencyVersion existe avec tous les champs
- [x] #2 La migration crée la table avec les index
- [x] #3 Un repository interface + impl Doctrine sont en place
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
- Entité `DependencyVersion` créée avec champs : id, dependencyName, packageManager, version, releaseDate, isLts, isLatest, createdAt
- Index unique sur (dependencyName, packageManager, version) + index de lookup
- Repository interface `DependencyVersionRepositoryInterface` avec findByNameAndManager, findLatestByNameAndManager, findByNameManagerAndVersion, save, clearLatestFlag
- Implémentation Doctrine `DoctrineDependencyVersionRepository`
- Alias services.yaml
- Migration `Version20260318225000` exécutée
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
