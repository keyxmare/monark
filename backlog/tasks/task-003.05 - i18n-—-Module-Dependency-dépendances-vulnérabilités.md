---
id: TASK-003.05
title: 'i18n — Module Dependency (dépendances, vulnérabilités)'
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

Extraire tous les textes hardcodés des pages Dependency (~6 pages Vue).

### Fichiers : DependencyList, DependencyDetail, DependencyForm, VulnerabilityList, VulnerabilityDetail, VulnerabilityForm

### Clés (~50)
- `dependency.dependencies.*` : Dependencies, Current, Latest, Package Manager, Outdated...
- `dependency.vulnerabilities.*` : CVE ID, Severity (critical/high/medium/low), Status (open/acknowledged/fixed/ignored)...
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Tous les textes hardcodés des pages Dependency extraits
- [ ] #2 Clés `dependency.*` ajoutées dans en.json et fr.json
- [ ] #3 Enums severity et status traduits
- [ ] #4 0 texte anglais en dur dans dependency/pages/
- [ ] #5 Tests passent
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
