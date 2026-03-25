---
id: TASK-099
title: Sync dépendances — OOM du worker sur les gros packages npm
status: Done
assignee: []
created_date: '2026-03-18 23:22'
updated_date: '2026-03-18 23:24'
labels:
  - bug
  - dependency
  - performance
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Le worker RabbitMQ crash en OOM (128MB) lors de la sync des versions de dépendances. Les réponses npm font 2-3 MB par package, et en traitant beaucoup de packages d'affilée, la mémoire explose.

## Fix
- Augmenter la limite mémoire du worker (`--memory-limit=512M`)
- Ou : découper la sync en sous-commandes par batch de packages (1 message par package au lieu d'un seul message pour tout)
- Appeler `gc_collect_cycles()` après chaque package traité
- Ne pas stocker toute la réponse npm en mémoire — parser uniquement le champ `time` sans charger `versions`
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le worker ne crash plus en OOM lors de la sync
- [x] #2 La sync complète fonctionne sur l'ensemble des dépendances
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
- Worker memory limit augmenté de 128M à 512M\n- `unset($data)` + `gc_collect_cycles()` dans NpmRegistryAdapter et PackagistRegistryAdapter après extraction des données\n- `gc_collect_cycles()` dans le handler après chaque package traité
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
