---
id: TASK-003.08
title: 'i18n — Backend (exceptions, messages d''erreur métier)'
status: In Progress
assignee: []
created_date: '2026-03-11 19:33'
updated_date: '2026-03-11 20:04'
labels:
  - i18n
  - backend
dependencies:
  - TASK-003.01
parent_task_id: TASK-003
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
### Objectif

Traduire les messages d'erreur métier backend via Symfony Translation.

### Fichiers concernés
- `NotFoundException.php` : `'%s with id "%s" was not found.'`
- `ExceptionListener.php` : `'Validation failed.'`

### Clés
- `error.not_found` / `error.validation_failed`
- Dans `translations/messages.en.yaml` et `translations/messages.fr.yaml`
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 NotFoundException utilise le translator Symfony
- [ ] #2 ExceptionListener utilise le translator pour les messages
- [ ] #3 Traductions en.yaml et fr.yaml contiennent les messages d'erreur
- [ ] #4 Tests backend passent sans régression
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
