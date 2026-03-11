---
id: TASK-003
title: Mettre en place l'i18n sur l'ensemble du projet
status: To Do
assignee: []
created_date: '2026-03-11 16:18'
updated_date: '2026-03-11 19:32'
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

Tâche parente pour l'internationalisation du projet Monark. Découpée en sous-tâches par périmètre.

~250-300 chaînes hardcodées en anglais à extraire sur 50 fichiers Vue + backend.

### Sous-tâches

1. **TASK-003a** — Infrastructure i18n (install vue-i18n, config, structure, plugin, backend translation config)
2. **TASK-003b** — i18n shared (sidebar, topbar, layouts, boutons communs, messages génériques)
3. **TASK-003c** — i18n Identity (login, register, profil, teams, access tokens, users)
4. **TASK-003d** — i18n Catalog (projets, providers, tech stacks, pipelines)
5. **TASK-003e** — i18n Dependency (dépendances, vulnérabilités)
6. **TASK-003f** — i18n Assessment (quiz, questions, réponses, tentatives)
7. **TASK-003g** — i18n Activity (dashboard, événements, notifications)
8. **TASK-003h** — i18n Backend (messages d'erreur métier, exceptions)
9. **TASK-003i** — i18n Stores (messages d'erreur dans les stores Pinia)

### Ordre d'exécution

TASK-003a en premier (infra), puis 003b (shared), puis les modules dans n'importe quel ordre, 003h et 003i en dernier.
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
