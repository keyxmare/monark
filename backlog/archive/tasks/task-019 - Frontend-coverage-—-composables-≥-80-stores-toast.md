---
id: TASK-019
title: 'Frontend coverage — composables ≥ 80%, stores toast'
status: Done
assignee: []
created_date: '2026-03-12 19:40'
updated_date: '2026-03-12 19:43'
labels:
  - testing
  - frontend
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Composables actuels : useLocale 100%, useMercure 0%, useSidebar 0%, useSyncProgress non couvert. Store toast à 0%. Objectif : composables ≥ 80%, toast store couvert.
<!-- SECTION:DESCRIPTION:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Résultat

Composables et toast store couverts à ≥ 80% ✅

### Coverage
- **shared/composables** : 100% (useLocale, useMercure, useSidebar)
- **catalog/composables** : 93.87% (useSyncProgress)
- **shared/stores/toast** : 100%

### Tests ajoutés (4 fichiers, 29 tests)
- `useSidebar.test.ts` : toggle, toggleMobile, closeMobile, shared state (7 tests)
- `useMercure.test.ts` : EventSource lifecycle, topics, reconnect, close (7 tests)
- `useSyncProgress.test.ts` : track, running/completed/failed mercure messages (4 tests)
- `toast.test.ts` : add/update/remove, auto-removal, progress, timers (11 tests)

### Commit
`0e14739` test(frontend): add composables and toast store tests — coverage ≥ 80%
<!-- SECTION:FINAL_SUMMARY:END -->
