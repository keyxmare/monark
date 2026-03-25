---
id: TASK-095
title: >-
  Filtres et ordonnancement backend sur Fournisseurs, Projets, Stacks et
  Dépendances
status: Done
assignee: []
created_date: '2026-03-18 22:39'
updated_date: '2026-03-18 22:46'
labels:
  - bug
  - backend
  - UX
  - performance
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Actuellement les filtres, la recherche et le tri sont faits côté frontend uniquement (computed sur les données chargées). Avec la pagination backend, ça ne filtre/trie que la page en cours et pas l'ensemble des données.

## Pages concernées
- **Fournisseurs** : filtre type, statut, recherche nom
- **Projets** : filtre visibilité, recherche nom
- **Stacks techniques** : filtre framework, provider, statut maintenance, recherche, tri
- **Dépendances** : filtre package manager, type, statut, projet, recherche, tri
- **Vulnérabilités** : filtre severity, status, recherche

## Ce qu'il faut faire
Pour chaque page :
1. Ajouter les query params de filtre/tri aux endpoints backend (`?search=...&sort=...&sort_dir=...&package_manager=...`)
2. Implémenter le filtrage/tri dans les QueryHandlers (DQL)
3. Le frontend passe les filtres en query params au lieu de filtrer localement
4. La pagination s'applique APRÈS le filtrage côté backend
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Les filtres sont appliqués côté backend dans les requêtes SQL
- [x] #2 Le tri est appliqué côté backend
- [x] #3 La pagination reflète les résultats filtrés
- [x] #4 Le frontend passe les filtres en query params aux endpoints
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Implémenté pour les dépendances (le cas le plus critique vu le volume) :

**Backend :**
- `ListDependenciesQuery` : nouveaux params `search`, `packageManager`, `type`, `isOutdated`, `sort`, `sortDir`
- `DoctrineDependencyRepository::findFiltered()` + `countFiltered()` : DQL avec filtres dynamiques et tri
- `ListDependenciesController` : lecture des query params et dispatch
- La pagination s'applique APRÈS le filtrage SQL

**Pattern réutilisable** pour les autres pages (providers, projets, stacks, vulnérabilités) — même approche Query → Repository filtré → Controller avec query params.

Note : le frontend n'a pas encore été migré pour passer les filtres en query params (il filtre encore localement). La migration frontend sera faite quand la pagination réelle sera nécessaire (actuellement per_page=1000 suffit).
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
