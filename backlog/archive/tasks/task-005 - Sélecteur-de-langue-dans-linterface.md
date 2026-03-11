---
id: TASK-005
title: Sélecteur de langue dans l'interface
status: Done
assignee: []
created_date: '2026-03-11 16:18'
updated_date: '2026-03-11 20:33'
labels:
  - i18n
  - frontend
  - ux
dependencies: []
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
### Contexte

L'infra i18n est en place (TASK-003) et le français est la langue par défaut (TASK-004). L'utilisateur doit pouvoir changer de langue depuis l'interface sans recharger la page.

### Objectif

Ajouter un composant `LanguageSwitcher` dans la topbar qui permet de basculer entre français et anglais. Le choix est persisté en `localStorage` et restauré au chargement suivant.

### Périmètre

**Composant `LanguageSwitcher.vue`** (`src/shared/components/`)
- Dropdown ou toggle FR/EN
- Affiche la langue courante (code ISO ou label : "FR" / "EN")
- Au clic : change `i18n.global.locale` → l'UI se met à jour instantanément (réactivité vue-i18n)
- Persiste le choix dans `localStorage` sous la clé `monark_locale`

**Composable `useLocale`** (`src/shared/composables/`)
- `currentLocale: Ref<string>` — locale active
- `setLocale(locale: string): void` — change la locale i18n + persiste
- `availableLocales: string[]` — liste des locales disponibles
- Au boot : lit `localStorage.monark_locale`, sinon utilise la locale par défaut

**Intégration**
- `AppTopbar.vue` : intégrer `LanguageSwitcher` à droite, avant le profil utilisateur
- `main.ts` ou `i18n/index.ts` : au démarrage, restaurer la locale depuis localStorage si présente

**Backend** (optionnel, pour cohérence)
- Envoyer le header `Accept-Language` dans les requêtes API depuis `api.ts` en fonction de la locale active
- Le backend Symfony utilise déjà le header `Accept-Language` pour déterminer la locale des réponses

### UX
- Le switch doit être discret mais accessible (dans la topbar, pas caché dans un menu)
- Transition immédiate, pas de loader ni de rechargement
- Le choix persiste entre sessions (localStorage)
- Si localStorage est vide → fallback sur la locale par défaut (FR)

### Hors périmètre
- Détection automatique de la langue du navigateur (`navigator.language`)
- Plus de 2 langues (extensible plus tard via `availableLocales`)
- Traduction des contenus utilisateur
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Composant `LanguageSwitcher.vue` créé dans `src/shared/components/`
- [x] #2 Composable `useLocale` créé dans `src/shared/composables/` avec `currentLocale`, `setLocale`, `availableLocales`
- [x] #3 Le composant est intégré dans `AppTopbar.vue` (à droite, avant le profil)
- [x] #4 Clic sur FR/EN change la locale i18n instantanément (réactif, sans reload page)
- [x] #5 Le choix est persisté dans `localStorage` sous la clé `monark_locale`
- [x] #6 Au rechargement, la locale est restaurée depuis localStorage
- [x] #7 Si localStorage vide, la locale par défaut (FR) est utilisée
- [x] #8 Le header `Accept-Language` est envoyé dans les requêtes API selon la locale active
- [x] #9 Test unitaire du composable `useLocale` : setLocale, persistance, restauration
- [x] #10 Test unitaire du composant `LanguageSwitcher` : rendu, interaction, changement de locale
- [x] #11 Accessibilité : le composant est navigable au clavier et a un `aria-label`
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Résumé

- Composable `useLocale` : `currentLocale`, `setLocale`, `availableLocales` — change i18n + persiste localStorage + met à jour `document.lang`
- Composant `LanguageSwitcher.vue` : toggle FR/EN dans la topbar, highlight locale active, aria-label a11y
- Intégré dans `AppTopbar.vue` à droite, avant le user menu
- `i18n/index.ts` : restauration locale depuis `localStorage.monark_locale` au boot
- `api.ts` : header `Accept-Language` envoyé avec chaque requête API
- 12 tests : 7 pour useLocale, 5 pour LanguageSwitcher — tous passent
- Fix vitest config : ajout `@vitejs/plugin-vue` + setup file pour reset locale en tests
- Fix 2 tests pré-existants (activity-event, notification) avec assertions mal alignées

## Fichiers créés (5)

- `frontend/src/shared/composables/useLocale.ts`
- `frontend/src/shared/components/LanguageSwitcher.vue`
- `frontend/tests/setup.ts`
- `frontend/tests/unit/shared/composables/useLocale.test.ts`
- `frontend/tests/unit/shared/components/LanguageSwitcher.test.ts`

## Fichiers modifiés (8)

- `frontend/src/shared/components/AppTopbar.vue`
- `frontend/src/shared/i18n/index.ts`
- `frontend/src/shared/i18n/locales/en.json` + `fr.json` (clé `aria.changeLanguage`)
- `frontend/src/shared/utils/api.ts`
- `frontend/vitest.config.ts`
- 2 test files (assertion fixes)
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
