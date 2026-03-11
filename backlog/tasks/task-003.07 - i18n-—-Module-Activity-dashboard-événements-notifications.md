---
id: TASK-003.07
title: 'i18n — Module Activity (dashboard, événements, notifications)'
status: To Do
assignee: []
created_date: '2026-03-11 19:33'
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

Extraire tous les textes hardcodés des pages Activity (~5 pages Vue).

### Fichiers : DashboardPage, ActivityEventList, ActivityEventDetail, NotificationList, NotificationDetail

### Clés (~25)
- `activity.dashboard.*` : Welcome to Monark...
- `activity.events.*` : Activity Events, Type, Entity Type, Payload...
- `activity.notifications.*` : Notifications, Channel, Read/Unread, Mark as read...
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Tous les textes hardcodés des pages Activity extraits
- [ ] #2 Clés `activity.*` ajoutées dans en.json et fr.json
- [ ] #3 0 texte anglais en dur dans activity/pages/
- [ ] #4 Tests passent
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
