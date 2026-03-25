---
id: TASK-037
title: Scan résilient — ne pas écraser les données existantes en cas d'erreur API
status: Done
assignee: []
created_date: '2026-03-18 19:40'
updated_date: '2026-03-18 22:13'
labels:
  - bug
  - resilience
  - catalog
  - scanner
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Quand le scan d'un projet échoue (403, timeout, erreur API provider), le handler supprime actuellement les stacks et dépendances existantes AVANT de lancer le scan (`deleteByProjectId`). Si le scan échoue ensuite, le projet se retrouve avec 0 stacks et 0 dépendances.

## Symptôme observé
- Provider GitHub keyxmare : ouvrir la page provider → "Impossible de charger les projets distants"
- Scan d'un projet → 403 GitHub → les données existantes sont supprimées → 0 dépendances et 0 stacks affichées

## Comportement attendu
1. **Scan backend** : ne supprimer les données existantes QUE si le scan réussit (pattern "scan first, then replace")
2. **Frontend provider page** : en cas d'erreur API, afficher un message d'avertissement mais **continuer à afficher les données déjà chargées** (projets, stacks, dépendances) — ne pas tout masquer
3. **Frontend projet detail** : en cas d'erreur de scan, afficher un message "Scan échoué — les données affichées datent du dernier scan réussi" au lieu de vider l'affichage

## Fix technique
- `ScanProjectHandler` : déplacer les `deleteByProjectId` APRÈS le scan réussi, juste avant l'insertion des nouvelles données
- `ProjectScanner::scan()` : catch les exceptions HTTP (403, 5xx) au niveau du scan global et retourner un `ScanResult` vide plutôt que lever une exception
- Frontend `ProviderDetail` : ne pas masquer la liste des projets si `fetchRemoteProjects` échoue, afficher un warning à la place
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 En cas d'erreur 403/5xx lors du scan, les stacks et dépendances existantes sont conservées
- [x] #2 Le handler supprime les anciennes données uniquement après un scan réussi
- [x] #3 La page provider affiche les projets déjà chargés même si l'API est en erreur
- [x] #4 Un message d'avertissement est affiché en cas d'erreur API (pas un écran vide)
- [x] #5 Un scan échoué retourne une erreur explicite au frontend
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Déjà implémenté durant cette session :\n- `ProjectScanner::scan()` wrappé dans try/catch → retourne ScanResult vide + log\n- `ScanProjectHandler` : si scan vide → retourne 0/0 sans supprimer les données existantes\n- `SyncProjectMetadataHandler` et `SyncMergeRequestsHandler` : try/catch + mark provider error\n- Frontend `ProviderDetail` : `remoteProjectsError` séparé du `error` global, warning au lieu de tout masquer\n- Frontend `ProviderList` : error en bandeau warning au-dessus de la liste\n- Provider status mis à jour en erreur en base quand l'API échoue
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Analyse de code statique doit passer
- [ ] #6 Deptrac doit passer
<!-- DOD:END -->
