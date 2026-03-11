---
id: TASK-003.09
title: i18n — Stores Pinia (messages d'erreur frontend)
status: To Do
assignee: []
created_date: '2026-03-11 19:33'
labels:
  - i18n
  - frontend
dependencies:
  - TASK-003.01
parent_task_id: TASK-003
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
### Objectif

Remplacer les messages d'erreur hardcodés dans les ~15 stores Pinia par des clés i18n.

### Pattern
`i18n.global.t('errors.failedToLoad', { entity: 'projects' })` ou clés spécifiques.

### Clés (~30)
- `errors.failedToLoad/Create/Update/Delete` avec paramètre `{entity}`
- `errors.invalidCredentials`, `errors.registrationFailed`...
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Tous les messages d'erreur hardcodés des stores remplacés par des clés i18n
- [ ] #2 Clés `errors.*` ajoutées dans en.json et fr.json
- [ ] #3 0 message d'erreur anglais en dur dans les stores
- [ ] #4 Tests frontend passent sans régression
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
