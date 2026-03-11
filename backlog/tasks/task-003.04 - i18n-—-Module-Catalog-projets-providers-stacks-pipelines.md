---
id: TASK-003.04
title: 'i18n — Module Catalog (projets, providers, stacks, pipelines)'
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

Extraire tous les textes hardcodés des pages Catalog (~8 pages Vue).

### Fichiers : ProjectList, ProjectDetail, ProjectForm, ProviderList, ProviderDetail, ProviderForm, TechStackList, TechStackForm, PipelineList, PipelineDetail

### Clés (~70)
- `catalog.projects.*` : Projects, Create Project, Scan, Visibility...
- `catalog.providers.*` : Providers, Test Connection, Import...
- `catalog.techStacks.*` : Tech Stacks, Language, Framework...
- `catalog.pipelines.*` : Pipelines, Status badges (pending, running, success, failed)...
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Tous les textes hardcodés des pages Catalog extraits
- [ ] #2 Clés `catalog.*` ajoutées dans en.json et fr.json
- [ ] #3 Status enum traduits (pending, running, success, failed)
- [ ] #4 0 texte anglais en dur dans catalog/pages/
- [ ] #5 Tests passent
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
