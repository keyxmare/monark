---
id: TASK-003.02
title: 'i18n — Module shared (layouts, navigation, boutons communs)'
status: Done
assignee: []
created_date: '2026-03-11 19:33'
updated_date: '2026-03-11 19:48'
labels:
  - i18n
  - frontend
dependencies:
  - TASK-003.01
parent_task_id: TASK-003
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
### Objectif

Extraire tous les textes hardcodés du module shared et les remplacer par des clés i18n `shared.*`.

### Fichiers concernés
- `AppSidebar.vue` : labels navigation (Dashboard, Catalog, Dependency, etc.), logo, aria-labels
- `AppTopbar.vue` : Logout, Open menu, Toggle sidebar
- `DashboardLayout.vue` : si textes présents

### Clés (~30)
- `shared.nav.*` : labels de navigation
- `shared.actions.*` : Save, Cancel, Delete, Edit, Back, Loading...
- `shared.states.*` : Loading..., No results, Error
- `shared.app.name` : Monark
- `shared.aria.*` : aria-labels accessibilité
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Tous les textes hardcodés de AppSidebar.vue extraits
- [ ] #2 Tous les textes hardcodés de AppTopbar.vue extraits
- [ ] #3 Clés `shared.*` ajoutées dans en.json et fr.json
- [ ] #4 0 texte anglais en dur dans les fichiers shared
- [ ] #5 Tests passent
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
