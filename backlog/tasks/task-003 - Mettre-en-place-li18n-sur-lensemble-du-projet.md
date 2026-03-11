---
id: TASK-003
title: Mettre en place l'i18n sur l'ensemble du projet
status: To Do
assignee: []
created_date: '2026-03-11 16:18'
updated_date: '2026-03-11 16:43'
labels:
  - i18n
  - frontend
  - backend
  - refactoring
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
### Contexte

Tous les textes de l'interface Monark sont actuellement en dur en anglais (labels, titres, messages d'erreur, placeholders, boutons). Pour supporter le multi-langue, il faut mettre en place l'infrastructure i18n côté backend et frontend.

### Objectif

Internationaliser l'ensemble du projet avec deux locales : `fr` (français) et `en` (anglais). C'est la tâche fondation — les tâches TASK-004 (locale par défaut) et TASK-005 (sélecteur de langue) en dépendent.

### Périmètre

**Frontend** (`vue-i18n`)
- Installer et configurer `vue-i18n` (mode Composition API)
- Créer la structure `src/shared/i18n/` :
  - `index.ts` : instance i18n, config, export
  - `locales/fr.json` : traductions françaises
  - `locales/en.json` : traductions anglaises
- Organisation des clés par module : `identity.*`, `catalog.*`, `dependency.*`, `assessment.*`, `activity.*`, `shared.*`
- Extraire **tous** les textes en dur des fichiers `.vue` : labels, titres de pages, boutons, messages d'état (loading, empty, error), colonnes de tableaux, tooltips, placeholders
- Remplacer par `{{ $t('key') }}` ou `t('key')` en `<script setup>`
- Enregistrer le plugin dans `main.ts`

**Backend** (Symfony Translation)
- Vérifier que le component Translation est bien actif (déjà inclus dans Symfony 8)
- Créer `translations/messages.fr.yaml` et `translations/messages.en.yaml`
- Extraire les messages d'erreur métier (NotFoundException, ValidationException, messages flash)
- Configurer `framework.default_locale` et `framework.translator.default_path`

**Modules à couvrir** (tous les textes visibles) :
- `shared` : sidebar, topbar, layouts, navigation, boutons communs (Save, Cancel, Delete, Edit, Back, Loading...)
- `identity` : login, register, profil, teams, access tokens, users
- `catalog` : projets, providers, tech stacks, pipelines
- `dependency` : dépendances, vulnérabilités
- `assessment` : quiz, questions, réponses, tentatives
- `activity` : dashboard, événements, notifications

### Points d'attention
- Les noms d'entités techniques (status enum values comme `pending`, `running`, `success`, `failed`) doivent aussi être traduits pour l'affichage
- Les messages d'erreur de validation Symfony sont déjà traduits par le framework — ne pas les redéfinir
- Garder les clés en anglais, format dot-notation : `catalog.projects.title`, `shared.actions.save`
- Ne pas toucher aux tests existants dans cette tâche — juste s'assurer qu'ils passent toujours

### Hors périmètre
- Locale par défaut → TASK-004
- Sélecteur de langue UI → TASK-005
- Traduction des contenus utilisateur (noms de projets, descriptions, etc.)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Package `vue-i18n` installé et configuré dans `main.ts`
- [ ] #2 Structure `src/shared/i18n/index.ts` + `locales/fr.json` + `locales/en.json` créée
- [ ] #3 Clés organisées par module : `shared.*`, `identity.*`, `catalog.*`, `dependency.*`, `assessment.*`, `activity.*`
- [ ] #4 100% des textes en dur extraits des fichiers `.vue` (0 texte anglais en dur restant dans les templates)
- [ ] #5 Fichiers `translations/messages.fr.yaml` et `translations/messages.en.yaml` créés côté backend
- [ ] #6 Messages d'erreur métier backend traduits (NotFoundException, messages custom)
- [ ] #7 Config Symfony `framework.translator` en place
- [ ] #8 Les 136 tests backend passent sans régression
- [ ] #9 Les 91 tests frontend passent sans régression
- [ ] #10 Changer la locale dans le code affiche bien les textes dans la langue choisie
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
