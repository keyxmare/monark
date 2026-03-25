---
id: TASK-038
title: >-
  Afficher l'écart temporel entre la version framework actuelle et la dernière
  LTS
status: Done
assignee: []
created_date: '2026-03-18 19:58'
updated_date: '2026-03-18 20:04'
labels:
  - feature
  - catalog
  - DX
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Sur l'onglet **Stacks techniques** de la page ProjectDetail, ajouter des colonnes indiquant la dernière version LTS du framework et le temps écoulé depuis.

## Objectif
Permettre de visualiser rapidement si un projet est à jour ou en retard par rapport à la LTS de son framework.

## Frameworks supportés
- **Symfony** (LTS via Packagist / endoflife.date API)
- **Vue.js** (LTS via npm registry / endoflife.date API)
- **Nuxt** (LTS via npm registry / endoflife.date API)

## Colonnes à ajouter
1. **Dernière LTS** : affiche la version LTS la plus récente du framework (ex: \"7.4\", \"3.5\")
2. **Écart LTS** : temps écoulé en format humanisé entre la version actuelle et la dernière LTS

## Affichage attendu
- Format humanisé : \"2 jours\", \"3 mois\", \"1 an\", \"7 ans\"
- Couleur selon l'écart :
  - Vert : < 6 mois
  - Orange : 6 mois – 2 ans
  - Rouge : > 2 ans
- Si pas de LTS connue ou pas de framework : afficher \"—\"

## Données nécessaires
- Version actuelle du framework (déjà présente dans TechStack)
- Dernière version LTS et sa date de release
- Ces données doivent être récupérées via une API externe (endoflife.date, Packagist, npm registry)

## Points à investiguer
- API endoflife.date semble couvrir Symfony, Vue et Nuxt
- Faut-il cacher les données LTS pour éviter de spammer l'API ?
- Faut-il stocker la LTS dans l'entité TechStack ou la calculer à la volée ?"
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Une colonne « Dernière LTS » affiche la version LTS la plus récente du framework
- [x] #2 Une colonne « Écart LTS » affiche le temps humanisé (jours, mois, ans)
- [x] #3 L'écart est coloré selon la criticité (vert/orange/rouge)
- [x] #4 Symfony, Vue.js et Nuxt sont supportés
- [x] #5 Les frameworks sans LTS connue affichent « — »
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Implémentation côté frontend uniquement via l'API publique endoflife.date (pas de changement backend).

**Fichiers créés :**
- `frontend/src/catalog/composables/useFrameworkLts.ts` — composable qui fetch et cache les données LTS pour Symfony, Vue.js et Nuxt via endoflife.date API
- `frontend/tests/unit/catalog/composables/useFrameworkLts.test.ts` — 11 tests (humanizeTimeDiff + ltsUrgency)

**Fichiers modifiés :**
- `frontend/src/catalog/pages/ProjectDetail.vue` — 2 colonnes ajoutées au tableau tech stacks (Dernière LTS + Écart LTS avec couleur vert/orange/rouge)
- `frontend/src/shared/i18n/locales/fr.json` — clés latestLts, ltsGap, upToDate
- `frontend/src/shared/i18n/locales/en.json` — idem

**Logique :**
- Symfony : filtre les cycles avec `lts: true`, prend le plus récent
- Vue.js / Nuxt : pas de LTS formelle, prend la dernière version stable
- Cache en mémoire par session (pas de re-fetch)
- Couleur : vert (< 6 mois), orange (6-24 mois), rouge (> 2 ans)
- Si version actuelle = LTS → affiche "À jour"
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
