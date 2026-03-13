---
id: TASK-017.14
title: Badge "Importé" cliquable → lien vers le projet local
status: Done
assignee: []
created_date: '2026-03-12 16:11'
updated_date: '2026-03-13 07:36'
labels:
  - frontend
  - backend
  - catalog
dependencies: []
parent_task_id: TASK-017
priority: low
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Item #15 — Le badge "Importé" sur les remote projects est un label statique. Le transformer en lien qui navigue vers le projet local correspondant.

## Prérequis
L'API des remote projects doit retourner l'ID du projet local quand il est importé (champ `localProjectId` ou similaire). Vérifier/ajouter cette donnée côté backend.

## Frontend
Transformer le `<span>` badge en `<RouterLink>` vers la page détail du projet.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Badge "Importé" est un lien cliquable
- [ ] #2 Clic navigue vers la page détail du projet local
- [ ] #3 L'API retourne l'ID du projet local associé
- [ ] #4 Style visuel indiquant que c'est cliquable (cursor pointer, underline hover)
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
