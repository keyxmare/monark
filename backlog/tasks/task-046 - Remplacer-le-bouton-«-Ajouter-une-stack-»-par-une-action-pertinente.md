---
id: TASK-046
title: Remplacer le bouton « Ajouter une stack » par une action pertinente
status: Done
assignee: []
created_date: '2026-03-18 20:33'
updated_date: '2026-03-18 20:38'
labels:
  - UX
  - catalog
dependencies: []
priority: low
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Le bouton « Ajouter une stack » est trompeur car les stacks sont détectées automatiquement par scan, pas créées manuellement. Le remplacer par un CTA plus pertinent comme « Scanner les projets » ou le supprimer.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le bouton Ajouter une stack est remplacé ou supprimé
- [x] #2 Si remplacé, l'action proposée est cohérente avec le workflow (ex: scan)
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Analyse de code statique doit passer
- [ ] #6 Deptrac doit passer
<!-- DOD:END -->
