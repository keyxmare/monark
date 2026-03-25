---
id: TASK-063
title: DependencyDetail — afficher les vulnérabilités liées en tableau
status: Done
assignee: []
created_date: '2026-03-18 21:48'
updated_date: '2026-03-18 21:54'
labels:
  - feature
  - dependency
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
La page DependencyDetail a une section « Vulnérabilités » vide avec juste un lien « Ajouter ». Afficher la liste des vulnérabilités liées à cette dépendance dans un tableau avec severity, statut, CVE ID et lien vers le détail.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Les vulnérabilités liées à la dépendance s'affichent en tableau
- [x] #2 Chaque vulnérabilité est cliquable vers sa page détail
- [x] #3 Les badges severity et status sont affichés
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
