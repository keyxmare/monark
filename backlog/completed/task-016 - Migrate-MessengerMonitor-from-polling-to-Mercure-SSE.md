---
id: TASK-016
title: Migrate MessengerMonitor from polling to Mercure SSE
status: Done
assignee: []
created_date: '2026-03-12 15:24'
updated_date: '2026-03-12 15:56'
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
- [x] #1 MessengerMonitor receives real-time updates via Mercure SSE
- [x] #2 No more setInterval polling in MessengerMonitor
- [x] #3 Initial load still fetches via REST endpoint
- [x] #4 Scheduler publishes stats every 5s only when data changes
- [x] #5 Queue and worker stats update in real-time
- [x] #6 EventSource closed on component unmount
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
PublishMessengerStatsCommand runs as messenger-stats-publisher Docker service, queries RabbitMQ every 5s and publishes to Mercure /messenger/stats only when data changes. MessengerMonitor.vue replaced setInterval with useMercure subscription + live connection indicator (green/red dot). Initial load still via REST. Commit: bdd48b8.
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
