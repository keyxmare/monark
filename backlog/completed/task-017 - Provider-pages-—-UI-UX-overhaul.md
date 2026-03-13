---
id: TASK-017
title: Provider pages — UI/UX overhaul
status: Done
assignee: []
created_date: '2026-03-12 16:10'
updated_date: '2026-03-13 07:36'
labels:
  - frontend
  - ui/ux
  - catalog
dependencies: []
references:
  - frontend/src/catalog/pages/ProviderList.vue
  - frontend/src/catalog/pages/ProviderDetail.vue
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Refonte UI/UX complète des pages ProviderList et ProviderDetail. Couvre les corrections critiques (suppression sans confirmation, feedback test connexion), le polish visuel (badges i18n, icônes, empty states), le redesign des layouts (cards, dropdown actions, fiche 2 colonnes), et les fonctionnalités manquantes (recherche, tri, select all, sync sélective).

## Fichiers concernés
- `frontend/src/catalog/pages/ProviderList.vue`
- `frontend/src/catalog/pages/ProviderDetail.vue`
- `frontend/src/shared/i18n/locales/fr.json`
- `frontend/src/shared/i18n/locales/en.json`
- `frontend/src/catalog/stores/provider.ts`
- `frontend/src/catalog/services/provider.service.ts`
<!-- SECTION:DESCRIPTION:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
