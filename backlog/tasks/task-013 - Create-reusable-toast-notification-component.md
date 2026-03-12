---
id: TASK-013
title: Create reusable toast notification component
status: To Do
assignee: []
created_date: '2026-03-12 15:21'
labels:
  - frontend
  - shared
  - ui
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Create a shared toast/snackbar notification system for ephemeral UI feedback.

## Scope

### Frontend — Shared
- **Toast component** (`frontend/src/shared/components/AppToast.vue`):
  - Variants: success, error, info, progress
  - Progress variant: displays a progress bar (current/total) with text
  - Auto-dismiss after configurable delay (default 5s)
  - Manual close button (X)
  - Smooth enter/leave transitions
  - Stacked display (multiple toasts, bottom-right or top-right)

- **Toast store** (`frontend/src/shared/stores/toast.ts`):
  - `addToast(options)` → returns toast ID
  - `updateToast(id, updates)` → update text, progress, variant
  - `removeToast(id)` → manual close
  - Auto-cleanup on dismiss
  - Options: `{ variant, title, message, duration?, progress?: { current, total } }`

- **Toast container** — mounted once in App.vue or DashboardLayout, renders all active toasts

### No backend changes needed
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Toast component renders success/error/info/progress variants
- [ ] #2 Progress variant shows progress bar with current/total
- [ ] #3 Auto-dismiss after configurable delay
- [ ] #4 Manual close via X button
- [ ] #5 Multiple toasts stack without overlapping
- [ ] #6 Toast store exposes addToast/updateToast/removeToast
- [ ] #7 Toast container mounted in layout
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
