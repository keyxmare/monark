---
id: TASK-020.03
title: Monark — ingestion et tracking des métriques coverage par projet
status: To Do
assignee: []
created_date: '2026-03-13 07:24'
labels:
  - feature
  - activity
  - ci
dependencies:
  - TASK-020.01
references:
  - src/Activity/Domain/
  - src/Activity/Presentation/Controller/
  - .github/workflows/ci.yml
parent_task_id: TASK-020
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Permettre à Monark de recevoir, stocker et afficher les métriques de coverage par projet dans le temps. C'est l'intégration "dogfood" : Monark track sa propre qualité.

**Contexte DDD :**
- Bounded context : **Activity** (enrichissement du dashboard et du suivi qualité)
- Nouvelle entité `BuildMetric` qui capture les résultats d'un run CI

**Entité `BuildMetric` :**
- `id` (UUID v7)
- `projectId` (UUID, FK vers Catalog.Project)
- `commitSha` (string)
- `ref` (string — branche/tag)
- `backendCoverage` (float, nullable)
- `frontendCoverage` (float, nullable)
- `mutationScore` (float, nullable)
- `createdAt` (DateTimeImmutable)

**API :**
- `POST /api/activity/projects/{projectId}/build-metrics` — ingestion depuis la CI (authentifié par token)
- `GET /api/activity/projects/{projectId}/build-metrics` — liste paginée, triée par date desc
- `GET /api/activity/projects/{projectId}/build-metrics/latest` — dernier résultat

**CI :**
- Ajouter un step en fin de workflow qui POST les métriques vers l'API Monark via `curl`
- Utiliser un secret `MONARK_API_TOKEN` pour l'authentification

**Dashboard :**
- Ajouter un widget "Coverage Trend" au dashboard Activity qui affiche l'évolution dans le temps

**Pré-requis :** TASK-020.01 (les artifacts coverage doivent exister pour être envoyés)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 L'entité `BuildMetric` existe avec les champs : projectId, commitSha, ref, backendCoverage, frontendCoverage, mutationScore, createdAt
- [ ] #2 L'endpoint POST `/api/activity/projects/{projectId}/build-metrics` accepte et persiste les métriques
- [ ] #3 L'endpoint GET `/api/activity/projects/{projectId}/build-metrics` retourne la liste paginée triée par date desc
- [ ] #4 L'endpoint GET `/api/activity/projects/{projectId}/build-metrics/latest` retourne la dernière métrique
- [ ] #5 Le workflow CI envoie les métriques à Monark en fin de run (step conditionnel, échec silencieux)
- [ ] #6 Les tests unitaires couvrent le handler, le controller et l'entité
- [ ] #7 La migration Doctrine est générée et appliquée
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
