---
id: TASK-027
title: Fix deptrac — 50 violations cross-context DDD
status: Done
assignee: []
created_date: '2026-03-13 12:45'
updated_date: '2026-03-13 13:11'
labels:
  - backend
  - architecture
  - ddd
  - ci
dependencies: []
references:
  - backend/deptrac.yaml
  - backend/src/Activity/Application/EventListener/
  - backend/src/Catalog/Infrastructure/Scanner/ProjectScanner.php
  - backend/src/Dependency/Domain/Model/Dependency.php
  - >-
    backend/src/Dependency/Application/CommandHandler/CreateDependencyHandler.php
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
La CI échoue sur `deptrac analyse` avec **50 violations** de dépendances entre bounded contexts. Les couches DDD ne respectent pas l'isolation : des contexts importent directement des classes d'autres contexts au lieu de passer par Shared.

**Violations par catégorie :**

### 1. Activity_Application → Catalog_Domain / Dependency_Domain (~20 violations)
Les event listeners (`CreateOutdatedDependencyTasksListener`, `CreateStackUpgradeTasksListener`, `CreateStalePrTasksListener`) importent directement des events, repos et modèles de Catalog et Dependency.
- **Fix** : Déplacer les domain events partagés (`ProjectScannedEvent`, `MergeRequestsSyncedEvent`) dans `Shared\Domain\Event`. Utiliser des interfaces dans `Activity\Domain\Port` pour les repos cross-context.

### 2. Catalog_Infrastructure → Dependency_Domain (~25 violations)
`ProjectScanner` crée des entités `Dependency` et `Vulnerability` directement, référençant `PackageManager`, `DependencyType`, etc.
- **Fix** : Le scanner ne doit pas créer les dépendances lui-même. Dispatcher un event/command vers le context Dependency pour qu'il gère ses propres entités.

### 3. Dependency_Domain → Catalog_Domain (5 violations)
L'entité `Dependency` a une relation ORM `ManyToOne` vers `Catalog\Domain\Model\Project`.
- **Fix** : Remplacer la relation par un simple `string $projectId` (ID reference). Le Domain ne doit jamais dépendre d'un autre context.

### 4. Dependency_Application → Catalog_Domain (1 violation)
`CreateDependencyHandler` importe `ProjectRepositoryInterface` de Catalog.
- **Fix** : Supprimer cette dépendance — le handler reçoit un `projectId` string, pas besoin de charger le Project.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 deptrac analyse passe sans violation (exit code 0)
- [x] #2 Les domain events partagés sont dans Shared\Domain\Event
- [x] #3 Dependency.entity utilise projectId (string) au lieu d'une relation vers Project
- [x] #4 ProjectScanner dispatch des commands au lieu de créer des entités Dependency directement
- [x] #5 Les tests backend passent
- [ ] #6 La migration Doctrine est générée si le schema change
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Résumé

Résolution des 50 violations deptrac cross-context DDD en appliquant le pattern Shared Kernel + Port/Adapter.

### Changements clés

- **Shared Kernel** : déplacement des VOs (`PackageManager`, `DependencyType`), events (`ProjectScannedEvent`, `MergeRequestsSyncedEvent`), et DTOs (`ScanResult`, `DetectedStack`, `DetectedDependency`) vers `Shared/Domain/`
- **Ports/Adapters cross-context** : création de `DependencyReaderPort`, `DependencyWriterPort`, `MergeRequestReaderPort` avec DTOs dédiés (`DependencyReadDTO`, `VulnerabilityReadDTO`, `MergeRequestReadDTO`)
- **Inversion de dépendance** : `GitProviderFactoryInterface` et `ProjectScannerInterface` pour découpler Application→Infrastructure
- **Découplage entité** : `Dependency.project` ManyToOne → `Dependency.projectId` UUID column
- **Migration** : drop FK constraint + index sur `dependencies.project_id`

### Stats

- 61 fichiers modifiés (+655 / -639 lignes)
- 16 nouveaux fichiers (Shared DTOs, events, ports, VOs, adapters, interfaces)
- 7 fichiers supprimés (anciens emplacements)
- 438 tests passent, 0 violations deptrac
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
