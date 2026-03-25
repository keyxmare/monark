---
id: TASK-036
title: Débugger la détection des dépendances lors du scan projet
status: Done
assignee: []
created_date: '2026-03-18 19:18'
updated_date: '2026-03-18 19:26'
labels:
  - bug
  - dependency
  - catalog
  - scanner
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Le scan de projet détecte correctement les **tech stacks** (frameworks, langages) mais ne remonte **aucune dépendance**.

## Symptôme observé
- Le projet Monark après scan affiche des frameworks détectés (ex: Symfony, Vue.js)
- Mais 0 dépendances remontées, alors que le projet a un `composer.json` et un `package.json` avec de nombreuses dépendances

## Investigation nécessaire
1. Vérifier le `ProjectScanner` — est-ce qu'il parse bien les fichiers de dépendances (`composer.json`, `composer.lock`, `package.json`, `pnpm-lock.yaml`) ?
2. Vérifier le `ScanProjectHandler` — est-ce que les dépendances détectées sont bien persistées via le `DependencyWriterPort` ?
3. Vérifier la chaîne complète : scanner → handler → port → repository
4. Vérifier si c'est un problème d'accès au contenu des fichiers via l'API Git (GitLab/GitHub) vs lecture locale
5. Vérifier les logs d'erreur éventuels lors du scan

## Résultat attendu
Après un scan, les dépendances du projet (composer + npm/pnpm) doivent être listées dans l'onglet Dépendances avec version courante, type (runtime/dev) et gestionnaire de paquets.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le scan d'un projet avec composer.json remonte les dépendances PHP
- [x] #2 Le scan d'un projet avec package.json remonte les dépendances JS/TS
- [x] #3 Les dépendances affichent la version courante et le type (runtime/dev)
- [x] #4 Le gestionnaire de paquets est correctement identifié (composer, npm, pnpm)
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Diagnostic

Le scanner (`ProjectScanner`) fonctionne correctement — il parse bien `composer.json`, `package.json`, `requirements.txt`, etc. et extrait les dépendances. Le `ScanProjectHandler` persiste correctement les dépendances via `DependencyWriterPort`.

**Le vrai problème** : le `ScanProjectController` avait été passé en mode async (202 Accepted + `{ message: 'Scan started' }`) lors de l'ajout du sync-all. Le frontend recevait un 202 sans résultat, affichait le toast avec 0 dépendances, et relançait immédiatement `dependencyStore.fetchAll()` — mais le worker RabbitMQ n'avait pas encore traité le scan.

## Fix

- **`ScanProjectController`** : injection directe du `ScanProjectHandler` au lieu du bus messenger → exécution synchrone, retour 200 avec le `ScanResultOutput` complet
- Le routing messenger garde `ScanProjectCommand: async` pour que le `SyncAllProjectsHandler` puisse continuer à dispatcher les scans en arrière-plan
- Test mis à jour pour vérifier le retour synchrone (200 + stacksDetected + dependenciesDetected)

**Fichiers modifiés :**
- `backend/src/Catalog/Presentation/Controller/ScanProjectController.php`
- `backend/tests/Unit/Catalog/Presentation/Controller/ProjectControllersTest.php`
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
