---
id: TASK-106
title: >-
  Dépendances introuvables sur les registres — afficher « Inconnu » et « Mort »
  au lieu de « À jour »
status: Done
assignee: []
created_date: '2026-03-19 07:50'
updated_date: '2026-03-19 08:02'
labels:
  - bug
  - dependency
  - UX
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Quand la sync des versions retourne un 404 (package introuvable sur npm/Packagist), la dépendance garde `latestVersion = currentVersion` et affiche « À jour » alors que c'est faux — le package n'existe tout simplement pas sur le registre.

## Comportement attendu
- Si aucune version n'a été trouvée sur le registre après sync → marquer la dépendance comme « morte »
- **latestVersion** : afficher « Inconnu »
- **Écart** : afficher « Inconnu »
- **Statut** : afficher « Mort » (nouveau badge violet ou gris foncé)

## Implémentation
- Ajouter un champ `registryStatus` (enum: `synced`, `not_found`, `pending`) sur l'entité Dependency
- Le handler `SyncSingleDependencyVersionHandler` : si le registre retourne vide (404), marquer `registryStatus = not_found`
- Le frontend affiche les labels appropriés selon le `registryStatus`
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Les dépendances introuvables sur les registres affichent Inconnu en version dernière
- [x] #2 L'écart affiche Inconnu au lieu de À jour
- [x] #3 Un badge Mort s'affiche en statut
- [x] #4 Le scan initial laisse le statut en pending
- [x] #5 Après sync réussie le statut passe à synced
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
**Backend :**
- Enum `RegistryStatus` (pending, synced, not_found)
- Champ `registryStatus` ajouté à l'entité Dependency (défaut `pending`)
- `SyncSingleDependencyVersionHandler` : marque `not_found` si registre vide + pas de version connue, `synced` sinon
- Migration + 51 `@bower_components/*` marqués `not_found` en base
- `DependencyOutput` expose `registryStatus`

**Frontend :**
- Type enrichi avec `registryStatus`
- Version dernière : affiche "Inconnu" en italique si `not_found`
- Écart : affiche "Inconnu" si `not_found`
- Badge statut : "Mort" (fond gris foncé) si `not_found`
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
