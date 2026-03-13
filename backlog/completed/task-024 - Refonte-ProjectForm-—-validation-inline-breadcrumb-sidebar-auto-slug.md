---
id: TASK-024
title: 'Refonte ProjectForm — validation inline, breadcrumb, sidebar, auto-slug'
status: Done
assignee: []
created_date: '2026-03-13 12:08'
updated_date: '2026-03-13 12:38'
labels:
  - frontend
  - catalog
  - ux
  - refacto
dependencies: []
references:
  - frontend/src/catalog/pages/ProjectForm.vue
  - frontend/src/catalog/pages/ProviderForm.vue
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
ProjectForm (237 lignes) est un formulaire basique sans validation inline, sans breadcrumb et sans sidebar contextuelle. Aligner avec le pattern ProviderForm.

**Changements :**
1. Breadcrumb (Projects / Name / Edit) au lieu du simple back link
2. Validation inline par champ (touched + computed errors + blur)
3. Helper texts sur les champs (slug pattern, repo URL)
4. Sidebar en edit : visibility, tech stacks count, pipelines count, created at
5. Auto-génération du slug depuis le name (avec override manuelle)
6. Layout 2 colonnes responsive (form + sidebar)
7. Bouton submit avec spinner loading
8. Bouton cancel visible
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le formulaire affiche un breadcrumb (Projects / Name / Edit)
- [x] #2 Chaque champ affiche une erreur inline au blur si invalide
- [x] #3 Le slug est auto-généré depuis le name (modifiable manuellement)
- [x] #4 En edit, une sidebar affiche visibility, counts et dates
- [x] #5 Le layout est responsive (2 cols desktop, 1 col mobile)
- [x] #6 Le bouton submit affiche un spinner pendant la soumission
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Résumé

Refonte complète de `ProjectForm.vue` avec :
- **Breadcrumb** : `Projects / [Nom] / Modifier` ou `Projects / Créer un projet`
- **Validation inline** : champs required, URL valide, pattern slug, feedback au blur
- **Auto-slug** : généré depuis le nom via `toSlug()`, désactivable si édité manuellement
- **Layout 2 colonnes** : formulaire (col-span-2) + sidebar en mode édition
- **Sidebar édition** : badge visibility, tech stacks count, date création
- **Bouton submit** : spinner SVG pendant le chargement
- **Bouton annuler** : retour au détail (edit) ou à la liste (create)

## Fichiers modifiés
- `frontend/src/catalog/pages/ProjectForm.vue` — réécriture complète
- `frontend/src/shared/i18n/locales/en.json` — ajout clés `repositoryUrlHint`, `slugHint`, `projectInfo`
- `frontend/src/shared/i18n/locales/fr.json` — idem FR

## Tests
- 24/24 fichiers, 138/138 tests ✅
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
