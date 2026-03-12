---
id: TASK-015
title: Setup Mercure hub infrastructure and Symfony bundle
status: To Do
assignee: []
created_date: '2026-03-12 15:22'
updated_date: '2026-03-12 15:24'
labels:
  - infrastructure
  - mercure
  - docker
dependencies: []
references:
  - docker/compose.yaml
  - backend/config/bundles.php
  - frontend/vite.config.ts
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Configure Mercure as the real-time push layer for the project. Mercure was planned in the initial stack but never wired up.

## Scope

### Docker
- Add Mercure service to `docker/compose.yaml` (dunglas/mercure image)
- Configure JWT secret, CORS allowed origins, publisher/subscriber URLs
- Expose Mercure hub port and configure frontend proxy (Vite dev server)

### Backend — Symfony
- Install `symfony/mercure-bundle` (already in bundles.php)
- Configure `config/packages/mercure.yaml`: hub URL, JWT secret
- Add `MERCURE_URL`, `MERCURE_PUBLIC_URL`, `MERCURE_JWT_SECRET` env vars
- Verify publisher service is injectable (`HubInterface`)

### Frontend
- Configure Vite proxy to forward `/.well-known/mercure` to Mercure hub
- **Shared composable** (`frontend/src/shared/composables/useMercure.ts`):
  - `subscribe(topic)` → returns reactive ref updated on each SSE event
  - Auto-close EventSource on component unmount
  - Reconnect on connection loss
- Verify EventSource connectivity from browser to hub

### No business logic — infrastructure only
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Mercure hub runs as Docker service
- [ ] #2 Backend can publish to Mercure hub via HubInterface
- [ ] #3 Frontend can subscribe to Mercure topics via EventSource
- [ ] #4 JWT auth configured between Symfony and Mercure hub
- [ ] #5 Vite dev proxy routes /.well-known/mercure correctly
- [ ] #6 Shared useMercure composable available with subscribe/auto-close/reconnect
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
