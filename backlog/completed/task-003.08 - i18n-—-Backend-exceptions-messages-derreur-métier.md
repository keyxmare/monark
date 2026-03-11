---
id: TASK-003.08
title: 'i18n — Backend (exceptions, messages d''erreur métier)'
status: Done
assignee: []
created_date: '2026-03-11 19:33'
updated_date: '2026-03-11 20:10'
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

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Résumé

- **NotFoundException** : ajout des propriétés `entity`/`entityId` (readonly, constructeur privé + factory `forEntity()`) pour permettre la traduction paramétrée côté infrastructure
- **ExceptionListener** : injection de `TranslatorInterface`, traduction des NotFoundException (clé `error.entity_not_found` avec params `%entity%`/`%id%`), DomainException (mapping clé par message connu), ValidationFailedException (clé `error.validation`)
- **LoginFailureHandler** : injection de `TranslatorInterface`, utilisation de `error.invalid_credentials`
- **messages.en.yaml / messages.fr.yaml** : 5 nouvelles clés (`entity_not_found`, `duplicate_email`, `duplicate_slug`, `invalid_credentials`, `project_not_linked`)
- Commit : `7f3697a`
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
