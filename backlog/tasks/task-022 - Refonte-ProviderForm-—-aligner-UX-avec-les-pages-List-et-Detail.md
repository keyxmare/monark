---
id: TASK-022
title: Refonte ProviderForm — aligner UX avec les pages List et Detail
status: Done
assignee: []
created_date: '2026-03-13 11:53'
updated_date: '2026-03-13 11:59'
labels:
  - frontend
  - catalog
  - ux
  - refacto
dependencies: []
references:
  - frontend/src/catalog/pages/ProviderForm.vue
  - frontend/src/catalog/pages/ProviderList.vue
  - frontend/src/catalog/pages/ProviderDetail.vue
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
La page ProviderForm (228 lignes) est en retard par rapport aux pages List et Detail du même module. Elle utilise un design basique (single card `max-w-lg`, formulaire plat) alors que les autres pages exploitent des grids responsive, des stats visuelles, des badges, et une UX riche.

**État actuel du formulaire :**
- Layout : simple card centrée, 6 champs empilés
- Aucun helper text / description sous les champs
- Validation uniquement post-submit (erreur globale, pas de feedback par champ)
- Champ `type` disabled en edit sans retour visuel (pas d'opacity/cursor)
- Pas de breadcrumb cohérent avec List/Detail
- Pas de preview du provider (icône, statut) en mode edit
- Pas de section contextuelle (ex: infos connexion, projets liés)

**Cible — aligner avec le niveau UX de List/Detail :**

1. **Layout enrichi** : header avec breadcrumb + icône provider (en edit), section form + section preview/info
2. **Validation inline** : feedback par champ (required, format URL, longueur), pas seulement erreur globale post-submit
3. **Helper texts** : descriptions sous les champs (URL placeholder dynamique déjà ok, ajouter textes pour username, token)
4. **État disabled visible** : opacity-50 + cursor-not-allowed sur le champ type en edit
5. **Preview provider en edit** : afficher statut connexion, nombre de projets, dernière sync (données déjà dans le store)
6. **Responsive** : form en 2 colonnes sur desktop (champs principaux + sidebar info)
7. **Bouton Test Connection** : permettre de tester la connexion directement depuis le formulaire (l'API existe déjà)
8. **Transitions** : loading state sur le bouton submit, disabled pendant soumission

**Hors scope :**
- Refacto du service API ou du store (déjà ok)
- Modification des champs eux-mêmes (la logique conditionnelle GitHub/username est correcte)
- Extraction en composable partagé avec ProjectForm (tâche séparée si pertinent)
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le formulaire affiche un breadcrumb cohérent avec List/Detail
- [x] #2 Chaque champ affiche un message d'erreur inline en cas de validation échouée (required, format)
- [x] #3 Les champs ont des helper texts explicatifs (username, token, URL)
- [x] #4 Le champ type en mode edit a un style disabled visible (opacity + cursor)
- [x] #5 En mode edit, une section latérale affiche le statut, projets count et dernière sync du provider
- [x] #6 Un bouton Test Connection est disponible dans le formulaire (mode edit)
- [x] #7 Le layout est responsive : 2 colonnes sur desktop, 1 colonne sur mobile
- [x] #8 Le bouton submit affiche un loading state pendant la soumission
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
ProviderForm refondu : layout 2 colonnes responsive, breadcrumb, validation inline par champ, helper texts, sidebar avec statut/projets/sync/test connection, spinner submit, bouton cancel. Fix Pest.php pour CI (guard is_dir sur Integration/Functional). 449 tests backend + 143 tests frontend passent, lint clean.
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
