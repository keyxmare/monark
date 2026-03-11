---
id: TASK-003.03
title: 'i18n — Module Identity (auth, profil, teams, tokens, users)'
status: Done
assignee: []
created_date: '2026-03-11 19:33'
updated_date: '2026-03-11 19:48'
labels:
  - i18n
  - frontend
dependencies:
  - TASK-003.02
parent_task_id: TASK-003
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
### Objectif

Extraire tous les textes hardcodés des pages Identity (~8 pages Vue).

### Fichiers : LoginPage, RegisterPage, ProfilePage, UserList, UserDetail, UserForm, TeamList, TeamDetail, TeamForm, AccessTokenList, AccessTokenForm

### Clés (~60)
- `identity.auth.*` : Sign in, Create account, Invalid credentials...
- `identity.profile.*` : Profile, First Name, Last Name...
- `identity.users.*` : Users, Email, Roles...
- `identity.teams.*` : Teams, Create Team, Members...
- `identity.tokens.*` : Access Tokens, Provider, Scopes...
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Tous les textes hardcodés des pages Identity extraits
- [ ] #2 Clés `identity.*` ajoutées dans en.json et fr.json
- [ ] #3 0 texte anglais en dur dans identity/pages/
- [ ] #4 Tests passent
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
