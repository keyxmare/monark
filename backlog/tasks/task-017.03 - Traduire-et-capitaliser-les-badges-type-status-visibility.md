---
id: TASK-017.03
title: 'Traduire et capitaliser les badges (type, status, visibility)'
status: Done
assignee: []
created_date: '2026-03-12 16:10'
updated_date: '2026-03-12 16:21'
labels:
  - frontend
  - ui/ux
  - i18n
dependencies: []
parent_task_id: TASK-017
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Item #3 — Les badges affichent les valeurs brutes en anglais (`gitlab`, `connected`, `pending`, `public`, `private`). Les passer par i18n avec capitalisation correcte.

## Badges concernés
- Type provider : `gitlab` → "GitLab", `github` → "GitHub", `bitbucket` → "Bitbucket"
- Status : `connected` → "Connecté", `pending` → "En attente", `error` → "Erreur"
- Visibility (remote projects) : `public` → "Public", `private` → "Privé"
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Tous les badges utilisent des clés i18n
- [x] #2 Traductions FR et EN présentes
- [x] #3 Capitalisation correcte dans les deux langues
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
