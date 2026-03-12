---
id: TASK-016
title: Migrate MessengerMonitor from polling to Mercure SSE
status: To Do
assignee: []
created_date: '2026-03-12 15:24'
labels:
  - fullstack
  - activity
  - mercure
dependencies:
  - TASK-015
references:
  - frontend/src/activity/pages/MessengerMonitor.vue
  - frontend/src/activity/stores/messenger.ts
  - backend/src/Activity/Infrastructure/Adapter/RabbitMqMonitor.php
  - backend/src/Activity/Presentation/Controller/GetMessengerStatsController.php
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Replace the 5s setInterval polling on MessengerMonitor.vue with Mercure SSE for real-time queue and worker stats.

## Context
Currently `MessengerMonitor.vue` uses `setInterval(() => messengerStore.fetchStats(), 5000)` to poll `GET /api/activity/messenger/stats`. This should use Mercure for consistency with the rest of the real-time stack.

## Scope

### Backend — Activity context
- **Symfony Scheduler command** (`PublishMessengerStatsCommand`):
  - Runs every 5s via Symfony Scheduler
  - Queries RabbitMQ Management API (reuse `RabbitMqMonitor`)
  - Publishes stats to Mercure topic `/messenger/stats`
  - Only publishes if data has changed since last publish (avoid noise)

- **Keep existing endpoint** `GET /api/activity/messenger/stats` as fallback for initial load

### Frontend — Activity
- **MessengerMonitor.vue changes**:
  - On mount: fetch initial stats via REST (one-time)
  - Subscribe to Mercure topic `/messenger/stats`
  - On each SSE event: update store reactively
  - Remove `setInterval` and auto-refresh toggle button
  - Close EventSource on unmount

- **Use shared `useMercure` composable** from TASK-014

### Dependencies
- Requires TASK-015 (Mercure infrastructure)
- Should use same `useMercure` composable as TASK-014
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 MessengerMonitor receives real-time updates via Mercure SSE
- [ ] #2 No more setInterval polling in MessengerMonitor
- [ ] #3 Initial load still fetches via REST endpoint
- [ ] #4 Scheduler publishes stats every 5s only when data changes
- [ ] #5 Queue and worker stats update in real-time
- [ ] #6 EventSource closed on component unmount
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
