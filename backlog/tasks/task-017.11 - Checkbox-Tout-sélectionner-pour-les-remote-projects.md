---
id: TASK-017.11
title: Checkbox "Tout sélectionner" pour les remote projects
status: In Progress
assignee: []
created_date: '2026-03-12 16:11'
updated_date: '2026-03-12 16:37'
labels:
  - frontend
  - ui/ux
  - catalog
dependencies: []
parent_task_id: TASK-017
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Item #12 — Pas de checkbox "tout sélectionner" pour les remote projects. Fastidieux quand il y a 50+ repos.

## Comportement
- Checkbox dans le header de la liste
- Sélectionne/désélectionne tous les projets NON importés de la page courante
- État indéterminé si sélection partielle
- Le compteur du bouton "Importer (N)" se met à jour en temps réel
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Checkbox "tout sélectionner" dans le header de la liste
- [ ] #2 Sélectionne uniquement les projets non importés
- [ ] #3 État indéterminé si sélection partielle
- [ ] #4 Désélection globale fonctionne
- [ ] #5 Compteur du bouton Importer se met à jour
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
