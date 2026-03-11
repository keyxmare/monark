---
id: TASK-006
title: >-
  Fix: les dépendances ne se persistent pas correctement (mapping Doctrine
  cassé)
status: Done
assignee: []
created_date: '2026-03-11 16:53'
updated_date: '2026-03-11 16:58'
labels:
  - bug
  - backend
  - dependency
  - doctrine
dependencies: []
references:
  - backend/src/Dependency/Domain/Model/Dependency.php
  - backend/src/Catalog/Domain/Model/Project.php
  - backend/migrations/Version20260311125050.php
  - >-
    backend/src/Dependency/Application/CommandHandler/CreateDependencyHandler.php
  - backend/src/Catalog/Application/CommandHandler/ScanProjectHandler.php
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
### Contexte

Les dépendances (`Dependency`) sont créées via le scan de projet (`ScanProjectHandler`) ou via l'API directe (`CreateDependencyController`), mais la persistance est fragile à cause de problèmes de mapping Doctrine et d'intégrité référentielle.

### Problèmes identifiés

#### 1. `projectId` est un scalaire, pas une relation Doctrine

**Fichier** : `backend/src/Dependency/Domain/Model/Dependency.php`

Le champ `projectId` est mappé comme un simple UUID :
```php
#[ORM\Column(type: 'uuid')]
private Uuid $projectId;
```

Alors que les autres entités liées à `Project` (comme `TechStack`, `Pipeline`) utilisent un vrai `ManyToOne` :
```php
#[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'techStacks')]
#[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
private Project $project;
```

**Conséquences** : Doctrine ne gère pas la relation, pas de lazy loading, pas de cascade, pas de validation côté ORM que le projet existe.

#### 2. Pas de foreign key en base

**Fichier** : `backend/migrations/Version20260311125050.php`

La table `dependencies` a une colonne `project_id UUID NOT NULL` mais **aucune contrainte FK** vers `catalog_projects`. Compare avec `catalog_pipelines` et `catalog_tech_stacks` qui ont leurs FK avec `ON DELETE CASCADE`.

**Conséquences** : Données orphelines possibles, pas de cascade delete en base, intégrité compromise.

#### 3. Pas de `OneToMany` côté `Project`

**Fichier** : `backend/src/Catalog/Domain/Model/Project.php`

`Project` a des `OneToMany` pour `$techStacks` et `$pipelines` mais **aucun pour les dépendances**. Le `ScanProjectHandler` appelle `deleteByProjectId()` puis recrée les dépendances, mais sans relation ORM le cycle de vie n'est pas garanti.

### Solution proposée

#### Backend

1. **Entité `Dependency`** : remplacer `$projectId (Uuid)` par `$project (ManyToOne → Project)` avec `JoinColumn(onDelete: CASCADE)`
2. **Entité `Project`** : ajouter `$dependencies (OneToMany → Dependency)` avec `cascade: ['persist', 'remove'], orphanRemoval: true`
3. **Migration Doctrine** : ajouter la FK `dependencies.project_id → catalog_projects.id ON DELETE CASCADE`
4. **DTOs** : adapter `CreateDependencyInput`, `DependencyOutput` pour continuer à exposer `projectId` (string) en API
5. **Handlers** : `CreateDependencyHandler` doit charger le `Project` depuis le repo avant d'assigner la relation
6. **Repository** : adapter `findByProjectId` et `deleteByProjectId` si nécessaire (le QueryBuilder utilise peut-être `d.projectId` au lieu de `d.project`)

#### Tests

- Test unitaire : créer une dépendance avec un projet valide → persist OK
- Test unitaire : créer une dépendance avec un projet inexistant → NotFoundException
- Test unitaire : vérifier que `DependencyOutput.projectId` retourne bien l'UUID string
- Vérifier que le scan projet recrée correctement les dépendances après suppression

### Fichiers impactés

| Fichier | Action |
|---|---|
| `backend/src/Dependency/Domain/Model/Dependency.php` | Remplacer `$projectId` par `$project` ManyToOne |
| `backend/src/Catalog/Domain/Model/Project.php` | Ajouter `$dependencies` OneToMany |
| `backend/src/Dependency/Application/DTO/DependencyOutput.php` | Adapter pour lire `$project->getId()` |
| `backend/src/Dependency/Application/DTO/CreateDependencyInput.php` | Garder `projectId` string |
| `backend/src/Dependency/Application/CommandHandler/CreateDependencyHandler.php` | Charger `Project` depuis repo |
| `backend/src/Dependency/Infrastructure/Persistence/Doctrine/DoctrineDependencyRepository.php` | Adapter queries |
| `backend/src/Catalog/Application/CommandHandler/ScanProjectHandler.php` | Adapter si nécessaire |
| `backend/migrations/VersionXXX.php` | Nouvelle migration : FK + potentiel rename |
| `backend/tests/Unit/Dependency/` | Adapter + nouveaux tests |
| `docs/api/openapi.yaml` | Pas de changement API (le champ reste `projectId` string) |

### Hors périmètre

- Changement d'API frontend (le contrat `projectId: string` ne change pas)
- Migration de données existantes (pas de données en prod)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Entité `Dependency` : `$project` est un `ManyToOne` vers `Project` avec `JoinColumn(onDelete: CASCADE)`
- [x] #2 Entité `Project` : `$dependencies` est un `OneToMany` vers `Dependency` avec `cascade: ['persist', 'remove'], orphanRemoval: true`
- [x] #3 Migration Doctrine : FK `dependencies.project_id → catalog_projects.id ON DELETE CASCADE` créée et exécutable
- [x] #4 DTO `DependencyOutput` retourne toujours `projectId` en string (pas de breaking change API)
- [x] #5 Handler `CreateDependencyHandler` charge le `Project` et lance `NotFoundException` si inexistant
- [x] #6 Le scan projet (`ScanProjectHandler`) recrée correctement les dépendances après suppression
- [x] #7 Supprimer un projet cascade-delete ses dépendances en base
- [x] #8 Test unitaire : création dépendance avec projet valide → OK
- [x] #9 Test unitaire : création dépendance avec projet inexistant → NotFoundException
- [x] #10 Test unitaire : `DependencyOutput::fromEntity()` retourne le bon `projectId`
- [x] #11 Les 136+ tests backend passent sans régression
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Résumé

Fix du mapping Doctrine pour l'entité `Dependency` : remplacement du scalaire `$projectId (Uuid)` par une vraie relation `$project (ManyToOne → Project)` avec FK et cascade delete.

## Changements

### Entités
- `Dependency.php` : `$projectId` → `$project` ManyToOne avec `JoinColumn(onDelete: CASCADE)`, ajout `getProject()`, `getProjectId()` conservé (délègue à `$project->getId()`)
- `Project.php` : ajout `$dependencies` OneToMany avec `cascade: ['persist', 'remove'], orphanRemoval: true` + getter

### Application layer
- `CreateDependencyHandler.php` : injecte `ProjectRepositoryInterface`, charge le projet, throw `NotFoundException` si inexistant
- `DependencyOutput::fromEntity()` : adapté pour `$dependency->getProject()->getId()`
- `ScanProjectHandler.php` : passe `$project` directement au lieu de l'UUID string

### Infrastructure
- `DoctrineDependencyRepository.php` : queries changées de `d.projectId` à `d.project`
- Nouvelle migration `Version20260311175622` : FK `dependencies.project_id → catalog_projects.id ON DELETE CASCADE` + index

### Tests
- 138 tests backend, 375 assertions, 0 failures (+2 tests vs avant)
- 91 tests frontend, 0 failures
- `DependencyFactory` et 8 fichiers de tests adaptés (Dependency::create prend `Project` au lieu de string)
- Nouveau test : `throws not found when project does not exist`
- Nouveau test : `returns correct projectId in output`
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
