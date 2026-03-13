---
id: TASK-024
title: 'Refonte ProjectForm — validation inline, breadcrumb, sidebar, auto-slug'
status: To Do
assignee: []
created_date: '2026-03-13 12:08'
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
- [ ] #1 Le formulaire affiche un breadcrumb (Projects / Name / Edit)
- [ ] #2 Chaque champ affiche une erreur inline au blur si invalide
- [ ] #3 Le slug est auto-généré depuis le name (modifiable manuellement)
- [ ] #4 En edit, une sidebar affiche visibility, counts et dates
- [ ] #5 Le layout est responsive (2 cols desktop, 1 col mobile)
- [ ] #6 Le bouton submit affiche un spinner pendant la soumission
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
