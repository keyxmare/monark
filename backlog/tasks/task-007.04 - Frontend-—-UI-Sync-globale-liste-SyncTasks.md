---
id: TASK-007.04
title: Frontend — UI Sync globale + liste SyncTasks
status: To Do
assignee: []
created_date: '2026-03-11 19:58'
labels:
  - frontend
  - activity
  - ui
dependencies:
  - TASK-007.01
  - TASK-007.02
references:
  - frontend/src/catalog/pages/ProviderDetail.vue
  - frontend/src/catalog/stores/provider.ts
  - frontend/src/catalog/services/provider.service.ts
  - frontend/src/activity/
parent_task_id: TASK-007
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Créer l'interface frontend pour déclencher la sync globale et visualiser les SyncTasks générées.

**Sync UI** :
- Bouton "Sync All" sur `ProviderDetail.vue` → appelle POST /api/catalog/providers/{id}/sync-all
- Feedback immédiat : toast/notification "Sync lancée pour N projets"
- État loading sur le bouton pendant l'appel

**SyncTasks UI** (nouvelle section dans Activity) :
- Page `SyncTaskList.vue` : liste des SyncTasks avec filtres (status, type, severity, projet)
- Badges severity avec couleurs (critical=rouge, high=orange, medium=jaune, low=bleu, info=gris)
- Actions inline : Acknowledge, Resolve, Dismiss (PATCH status)
- Compteurs stats en haut de page (via GET /api/activity/sync-tasks/stats)
- Lien vers le projet concerné

**Dashboard integration** :
- Widget sur le dashboard Activity montrant les SyncTasks critiques/high ouvertes
- Compteur total de tâches ouvertes dans la sidebar/navigation

**Existant à réutiliser** :
- Pattern de stores Pinia (voir useProjectStore, useProviderStore)
- Pattern de services API (voir project.service.ts, provider.service.ts)
- Composants UI existants (tables, badges, boutons, filtres)
- Layout dashboard existant
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 ProviderDetail affiche un bouton 'Sync All' qui déclenche POST /api/catalog/providers/{id}/sync-all
- [ ] #2 Après clic, un toast confirme 'Sync lancée pour N projets' avec le count retourné par l'API
- [ ] #3 Page SyncTaskList affiche la liste des SyncTasks avec colonnes : titre, type, severity, projet, status, date
- [ ] #4 Les filtres par status, type, severity et projet fonctionnent correctement
- [ ] #5 Les actions Acknowledge/Resolve/Dismiss mettent à jour le status via PATCH et rafraîchissent la liste
- [ ] #6 Les badges severity utilisent les bonnes couleurs (critical=rouge, high=orange, medium=jaune, low=bleu, info=gris)
- [ ] #7 Les stats (compteurs par type/severity) sont affichés en haut de la page SyncTaskList
- [ ] #8 Un widget dashboard affiche le nombre de SyncTasks critiques/high ouvertes
- [ ] #9 Tests Vitest : store actions, service calls, rendu composants avec données mockées
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
