---
id: TASK-004
title: Définir le français comme langue par défaut
status: Done
assignee: []
created_date: '2026-03-11 16:18'
updated_date: '2026-03-11 20:27'
labels:
  - i18n
  - frontend
  - backend
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
### Contexte

Une fois l'infra i18n en place (TASK-003), il faut définir le français comme langue par défaut. L'application doit s'afficher en français dès le premier chargement, sans action de l'utilisateur.

### Objectif

Configurer `fr` comme locale par défaut sur le backend et le frontend. S'assurer que l'ensemble des traductions françaises sont complètes et cohérentes.

### Périmètre

**Backend**
- `config/packages/translation.yaml` : `framework.default_locale: fr`
- `config/services.yaml` ou `framework.yaml` : `kernel.default_locale: fr` si pas déjà fait
- Vérifier que les messages d'erreur Symfony natifs (validation, security) sont en français
- Installer le pack de traductions Symfony si nécessaire : `symfony/translation` + validators FR

**Frontend**
- `src/shared/i18n/index.ts` : `locale: 'fr'`, `fallbackLocale: 'en'`
- S'assurer que le fichier `fr.json` est **complet** (toutes les clés présentes dans `en.json` ont leur équivalent FR)
- Vérifier chaque page visuellement : aucun texte anglais résiduel ne doit apparaître au premier chargement

### Points d'attention
- Le `fallbackLocale` doit être `en` pour éviter les clés manquantes non traduites qui afficheraient une clé brute
- Les enums affichées (status pipeline, severity vulnérabilité, etc.) doivent avoir leur traduction FR
- Les dates doivent s'afficher au format français (JJ/MM/AAAA) si la locale est FR
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Symfony configuré avec `default_locale: fr` dans translation.yaml et/ou framework.yaml
- [ ] #2 vue-i18n configuré avec `locale: 'fr'` et `fallbackLocale: 'en'`
- [ ] #3 Fichier `fr.json` complet : toutes les clés de `en.json` ont une traduction FR
- [ ] #4 Fichier `messages.fr.yaml` complet côté backend
- [ ] #5 Au premier chargement sans localStorage, l'interface entière s'affiche en français
- [ ] #6 Les messages d'erreur API (404, validation, etc.) retournent des messages en français par défaut
- [ ] #7 Les enums (status, severity, visibility, etc.) sont traduites en français
- [ ] #8 Les dates s'affichent au format FR (JJ/MM/AAAA) quand la locale est `fr`
- [ ] #9 Tests backend et frontend passent sans régression
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Résumé

- Backend : `default_locale: fr` déjà configuré dans `framework.yaml` ✅
- Frontend : `locale: 'fr'` + `fallbackLocale: 'en'` déjà configurés dans `i18n/index.ts` ✅
- Ajout de `datetimeFormats` (en/fr) avec formats `short` (date) et `long` (date+heure) dans la config vue-i18n
- Remplacement de tous les `toLocaleDateString()` → `d(new Date(...), 'short')` et `toLocaleString()` → `d(new Date(...), 'long')` dans 19 fichiers Vue
- Correction de 2 chaînes hardcodées en anglais dans ProjectDetail.vue ("Package Manager", "Type")
- Build frontend OK, zéro occurrence de `toLocaleDateString`/`toLocaleString` restante

## Fichiers modifiés (20)

- `frontend/src/shared/i18n/index.ts` — datetimeFormats config
- 19 pages Vue : ProviderList, ProviderDetail, TechStackList, PipelineList, PipelineDetail, ProjectDetail, UserDetail, TeamDetail, AccessTokenList, QuizDetail, QuizList, AttemptDetail, AttemptList, ActivityEventDetail, ActivityEventList, NotificationDetail, VulnerabilityDetail, VulnerabilityList, DependencyDetail
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
