---
id: TASK-002
title: Ajouter le lien vers le repo de la dépendance (Dependency)
status: To Do
assignee: []
created_date: '2026-03-11 16:18'
updated_date: '2026-03-11 16:43'
labels:
  - dependency
  - feature
  - backend
  - frontend
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
### Contexte

Dans le module Dependency, chaque dépendance (`Dependency`) est listée avec son nom, version courante, version latest, package manager, etc. Mais il manque le lien direct vers le repository source de la dépendance (ex: `https://github.com/symfony/symfony`), ce qui oblige l'utilisateur à aller chercher manuellement.

### Objectif

Ajouter un champ `repositoryUrl` (nullable) sur l'entité `Dependency` et l'exposer dans l'API + l'interface. Le lien doit être cliquable et s'ouvrir dans un nouvel onglet.

### Périmètre

**Backend** (`src/Dependency/`)
- Entité `Dependency` : nouveau champ `repositoryUrl` (nullable string, max 2048 chars)
- Migration Doctrine pour ajouter la colonne
- DTO `DependencyOutput` : exposer le champ
- DTO `CreateDependencyInput` / `UpdateDependencyInput` : accepter le champ (optionnel)
- Validation : URL valide si renseignée (`@Assert\Url`)
- OpenAPI : documenter le champ sur tous les endpoints Dependency

**Frontend** (`src/dependency/`)
- Type `Dependency` : ajouter `repositoryUrl: string | null`
- Page liste des dépendances : afficher le lien cliquable (icône externe) si renseigné
- Page détail dépendance : afficher le lien complet
- ProjectDetail > tab Dependencies (si applicable) : afficher le lien

### Hors périmètre
- Résolution automatique de l'URL depuis le package manager (futur)
- Validation que l'URL est accessible
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Champ `repositoryUrl` (nullable, string max 2048) ajouté sur l'entité `Dependency`
- [ ] #2 Migration Doctrine générée et exécutable sans erreur
- [ ] #3 Validation `@Assert\Url` sur le champ si non-null
- [ ] #4 Le champ est accepté en entrée (create/update) et retourné en sortie (DTO)
- [ ] #5 API GET `/dependency/dependencies` et GET `/dependency/dependencies/{id}` retournent `repositoryUrl`
- [ ] #6 OpenAPI mis à jour avec le champ `repositoryUrl` sur les schemas Dependency
- [ ] #7 Liste frontend : lien cliquable avec `target=_blank` et `rel=noopener` si URL présente, tiret sinon
- [ ] #8 Détail frontend : URL complète affichée avec lien externe
- [ ] #9 Tests unitaires backend : handler create/update avec et sans repositoryUrl
- [ ] #10 Tests unitaires frontend : store et affichage conditionnel du lien
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
