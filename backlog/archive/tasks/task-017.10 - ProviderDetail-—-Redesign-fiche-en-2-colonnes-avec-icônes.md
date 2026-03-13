---
id: TASK-017.10
title: ProviderDetail — Redesign fiche en 2 colonnes avec icônes
status: Done
assignee: []
created_date: '2026-03-12 16:11'
updated_date: '2026-03-12 16:37'
labels:
  - frontend
  - ui/ux
  - catalog
dependencies: []
parent_task_id: TASK-017
priority: low
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Item #11 — La section fiche (`<dl>`) est fonctionnelle mais plate. Passer sur un layout 2 colonnes avec icônes par champ pour donner plus de relief.

## Design
- Layout grid 2 colonnes (1 colonne mobile)
- Icônes par champ : calendrier (dates), globe (URL), user (username), tag (type), signal (statut)
- Valeurs mieux typographiées (taille, weight)
- Boutons d'action regroupés dans un header card plus structuré
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Layout 2 colonnes pour les champs de la fiche
- [x] #2 Icône associée à chaque champ
- [x] #3 Responsive : 1 colonne sur mobile
- [x] #4 Boutons d'action regroupés dans un header structuré
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
