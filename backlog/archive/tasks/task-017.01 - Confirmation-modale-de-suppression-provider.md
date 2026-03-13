---
id: TASK-017.01
title: Confirmation modale de suppression provider
status: Done
assignee: []
created_date: '2026-03-12 16:10'
updated_date: '2026-03-12 16:18'
labels:
  - frontend
  - ui/ux
  - catalog
dependencies: []
parent_task_id: TASK-017
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Items #1 et #18 — Ajouter un dialogue de confirmation avant la suppression d'un provider (ProviderList + ProviderDetail). La modale doit afficher le nom du provider et le nombre de projets importés qui seront affectés.

## Composant
Créer un composant `ConfirmDialog.vue` réutilisable (ou utiliser le pattern existant si présent).

## Pages impactées
- ProviderList.vue → bouton "Supprimer" inline
- ProviderDetail.vue → bouton "Supprimer" en header
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Clic sur Supprimer ouvre une modale de confirmation
- [x] #2 La modale affiche le nom du provider et le nombre de projets affectés
- [x] #3 Bouton Annuler ferme la modale sans action
- [x] #4 Bouton Confirmer supprime le provider
- [x] #5 Fonctionne sur ProviderList et ProviderDetail
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
