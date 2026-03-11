---
id: TASK-003.01
title: i18n — Infrastructure (vue-i18n + Symfony Translation)
status: In Progress
assignee: []
created_date: '2026-03-11 19:32'
updated_date: '2026-03-11 19:34'
labels:
  - i18n
  - frontend
  - backend
  - infra
dependencies: []
parent_task_id: TASK-003
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
### Objectif

Mettre en place l'infrastructure i18n côté frontend (vue-i18n) et backend (Symfony Translation) sans extraire les textes.

### Frontend
- Installer `vue-i18n@^10` via pnpm dans Docker
- Créer `src/shared/i18n/index.ts` : instance i18n, config, export
- Créer `src/shared/i18n/locales/en.json` et `src/shared/i18n/locales/fr.json` (structure vide par module)
- Enregistrer le plugin dans `main.ts`
- Locale par défaut : `en` (TASK-004 changera en `fr`)

### Backend
- Vérifier/configurer `framework.translator` dans `config/packages/translation.yaml`
- Créer `translations/messages.en.yaml` et `translations/messages.fr.yaml` (structure vide)
- Configurer `framework.default_locale: en`
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Package `vue-i18n` installé dans `package.json`
- [ ] #2 Fichier `src/shared/i18n/index.ts` créé avec instance i18n exportée
- [ ] #3 Fichiers `locales/en.json` et `locales/fr.json` créés avec structure par module
- [ ] #4 Plugin enregistré dans `main.ts`
- [ ] #5 Config Symfony `framework.translator` en place
- [ ] #6 Fichiers `translations/messages.en.yaml` et `translations/messages.fr.yaml` créés
- [ ] #7 Tests frontend et backend passent sans régression
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
