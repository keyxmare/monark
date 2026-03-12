---
id: TASK-017.04
title: Remplacer les actions inline par un menu dropdown (ProviderList)
status: Done
assignee: []
created_date: '2026-03-12 16:10'
updated_date: '2026-03-12 16:24'
labels:
  - frontend
  - ui/ux
  - catalog
dependencies: []
parent_task_id: TASK-017
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Items #4 et #8 — Les 4 actions textuelles (Tester / Voir / Modifier / Supprimer) sont trop denses dans la dernière colonne du tableau. Les remplacer par un bouton kebab (⋮) ouvrant un dropdown menu. Garder "Voir" accessible via clic sur la ligne entière.

## Composant
Créer un composant `DropdownMenu.vue` réutilisable avec :
- Trigger (bouton kebab)
- Items (label, icon optionnel, variant danger pour Supprimer)
- Fermeture au clic extérieur
- Positionnement auto (haut/bas selon l'espace disponible)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Bouton kebab ⋮ remplace les 4 actions inline
- [x] #2 Dropdown avec Tester / Voir / Modifier / Supprimer
- [x] #3 Supprimer en rouge (variant danger)
- [x] #4 Fermeture au clic extérieur et Escape
- [x] #5 Clic sur la ligne navigue vers le détail
- [x] #6 Composant DropdownMenu réutilisable
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
