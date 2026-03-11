---
id: TASK-007
title: Sync globale projets & création automatique de SyncTasks
status: To Do
assignee: []
created_date: '2026-03-11 19:57'
labels:
  - feature
  - catalog
  - activity
  - async
dependencies: []
references:
  - backend/src/Catalog/Application/CommandHandler/ScanProjectHandler.php
  - backend/src/Catalog/Infrastructure/Scanner/ProjectScanner.php
  - backend/src/Catalog/Infrastructure/GitProvider/GitLabClient.php
  - backend/src/Catalog/Domain/Model/Provider.php
  - backend/src/Catalog/Domain/Model/Project.php
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Permettre de lancer une synchronisation sur l'ensemble des projets importés (par provider ou globalement) et générer automatiquement des tâches d'action (SyncTask) à partir des résultats de scan.

**Contexte actuel** :
- Le scan est unitaire (un projet à la fois, déclenché manuellement)
- Aucun mécanisme de sync globale
- Aucune entité de type "tâche d'action" générée automatiquement

**Objectif** :
- Déclencher un scan async sur tous les projets d'un provider (ou tous les projets)
- Après chaque scan, analyser les résultats et créer des SyncTasks : deps outdated, vulnérabilités CVE, stacks obsolètes, changements metadata
- Dédoublonner les tâches (ne pas recréer si une tâche ouverte existe déjà pour le même problème)

**Décisions architecture à prendre** :
- Context de SyncTask : Activity (journal d'activité) vs Catalog
- Granularité : une tâche par problème vs agrégé par projet/type
- Scheduling futur : Symfony Scheduler pour sync périodique (hors scope initial, mais à prévoir)
<!-- SECTION:DESCRIPTION:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
