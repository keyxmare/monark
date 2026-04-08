# Design: Global Sync Workflow

**Date**: 2026-03-31
**Objectif**: Unifier tous les boutons de synchronisation en un seul workflow global avec bandeau de progression persistant (fil d'Ariane + progress bar).
**Motivation**: Actuellement plusieurs boutons de sync disperses (Sync All sur Frameworks, Sync Versions sur Dependencies) avec des toasts individuels. L'utilisateur ne sait pas ce qui tourne.

---

## 1. Concept

- Un seul bouton "Synchroniser" sur 5 pages : Projets, Langages, Frameworks, Dependances, Vulnerabilites
- Un seul workflow a la fois (mutex backend)
- 3 steps sequentielles avec progression en temps reel
- Bandeau persistant dans le layout (survit a la navigation et au refresh)
- Suppression des toasts de sync et des boutons individuels

---

## 2. Les 3 Steps

| Step | Nom | Action | Donnees alimentees |
|---|---|---|---|
| 1 | Sync Projets | Metadata + scan structure (detecte langages, frameworks, deps) + merge requests | Projets, Langages, Frameworks, Dependances |
| 2 | Sync Versions | Registries (npm/packagist/pypi) pour deps + endoflife.date pour frameworks/langages | Dependances (latest, outdated), Frameworks (LTS, gap, EOL), Langages (EOL) |
| 3 | Scan CVE | **Stub** — no-op, complete immediatement | Vulnerabilites (futur) |

Chaque step emet sa progression via Mercure. La step suivante demarre quand la precedente est terminee.

---

## 3. Backend

### Entite GlobalSyncJob

Table: `global_sync_jobs`

| Champ | Type | Description |
|---|---|---|
| id | UUID | Primary key |
| status | enum (running, completed, failed) | Statut global |
| current_step | int (1, 2, 3) | Step en cours |
| current_step_name | string | Nom lisible (sync_projects, sync_versions, scan_cve) |
| step_progress | int | Progression de la step courante |
| step_total | int | Total de la step courante |
| created_at | DateTimeImmutable | |
| completed_at | DateTimeImmutable (nullable) | |

### Endpoints

**`POST /api/v1/sync`** — Lance un workflow global
- Verifie qu'aucun sync n'est en cours (sinon 409 Conflict)
- Cree le GlobalSyncJob avec status=running, current_step=1
- Dispatch `GlobalSyncCommand`
- Retourne `{ syncId, status: "running", currentStep: 1 }`

**`GET /api/v1/sync/current`** — Retourne le sync en cours
- Cherche un GlobalSyncJob avec status=running
- Retourne le job ou `{ data: null }` si aucun sync en cours

### Handler GlobalSyncHandler

Orchestre les 3 steps sequentiellement :

**Step 1 — Sync Projets** :
- Recupere tous les projets
- Met a jour GlobalSyncJob: current_step=1, step_total=nb_projets
- Dispatch `ScanProjectCommand` + `SyncProjectMetadataCommand` par projet
- Chaque completion de projet: increment step_progress, publie sur Mercure
- Quand tous les projets sont termines: passe a step 2

**Step 2 — Sync Versions** :
- Met a jour GlobalSyncJob: current_step=2
- Part A: Dispatch `SyncDependencyVersionsCommand` (registry sync pour les deps)
- Part B: Dispatch `SyncProductVersionsCommand` (endoflife.date pour frameworks/langages)
- Progresse par package traite
- Quand tout est termine: passe a step 3

**Step 3 — Scan CVE (stub)** :
- Met a jour GlobalSyncJob: current_step=3, step_progress=0, step_total=0
- Marque immediatement completed
- Publie event final sur Mercure

### Mercure

Topic unique: `/global-sync/{syncId}`

Payload a chaque update:
```json
{
  "syncId": "uuid",
  "status": "running",
  "currentStep": 2,
  "currentStepName": "sync_versions",
  "stepProgress": 145,
  "stepTotal": 421,
  "completedSteps": ["sync_projects"],
  "message": "vue@3.5.31"
}
```

Payload final:
```json
{
  "syncId": "uuid",
  "status": "completed",
  "currentStep": 3,
  "currentStepName": "scan_cve",
  "stepProgress": 0,
  "stepTotal": 0,
  "completedSteps": ["sync_projects", "sync_versions", "scan_cve"]
}
```

### Mutex

Avant de creer un GlobalSyncJob, le handler verifie qu'aucun job avec status=running n'existe. Si oui, le controller retourne 409 Conflict. Pas de lock distribue — une simple query DB suffit pour un seul consumer.

---

## 4. Frontend

### Composable `useGlobalSync` (shared)

```
State:
  currentSync: GlobalSyncState | null
  isRunning: computed boolean

Methods:
  startSync() -> POST /api/v1/sync
  loadCurrent() -> GET /api/v1/sync/current (appele au mount)

Mercure:
  S'abonne a /global-sync/{syncId} quand un sync est en cours
  Met a jour currentSync a chaque message
  Quand status=completed: ferme la connexion, null currentSync apres 3s
```

Le composable est instancie dans `DashboardLayout` — donc partage entre toutes les pages via provide/inject.

### Composant `SyncProgressBanner` (shared)

Affiche dans le DashboardLayout, au-dessus du contenu de page.

**Fil d'Ariane** :
```
✓ Sync Projets ──── ● Sync Versions (145/421) ──── ○ Scan CVE
```

- ✓ = step completee (vert)
- ● = step en cours (bleu, avec progress bar en dessous)
- ○ = step a venir (gris)

**Progress bar** : sous la step active, affiche `stepProgress / stepTotal`

**Message** : nom du dernier element traite (ex: "vue@3.5.31")

**Disparition** : 3 secondes apres completion (toutes les steps vertes), le bandeau disparait avec une animation fade-out.

### Composant `SyncButton` (shared)

Bouton reutilisable sur les 5 pages.

- **Au repos** : bouton primaire "Synchroniser" avec icone refresh
- **Pendant sync** : bouton disabled "Sync en cours — Step 2/3" avec spinner

### Pages affectees

| Page | Ajout | Suppression |
|---|---|---|
| `DashboardLayout` | `<SyncProgressBanner />` + provide `useGlobalSync` | - |
| `ProjectList.vue` | `<SyncButton />` dans le header | - |
| `LanguageList.vue` | `<SyncButton />` dans le header | - |
| `FrameworkList.vue` | `<SyncButton />` dans le header | Bouton "Sync All" existant |
| `DependencyList.vue` | `<SyncButton />` dans le header | Bouton "Sync Versions" existant |
| `VulnerabilityList.vue` | `<SyncButton />` dans le header | - |

### Refresh automatique des donnees

Quand une step complete, les pages concernees rechargent:
- Step 1 complete → stores: project, language, framework, dependency rechargent
- Step 2 complete → stores: language, framework, dependency rechargent
- Step 3 complete → store: vulnerability recharge (no-op pour l'instant)

Le composable `useGlobalSync` emet les events via un watcher. Chaque page ecoute et rafraichit ses stores.

### Suppression

- `frontend/src/catalog/composables/useSyncProgress.ts` — remplace par useGlobalSync
- `frontend/src/dependency/composables/useDependencySyncProgress.ts` — remplace par useGlobalSync
- Toast de sync dans les pages
- Boutons de sync individuels

---

## 5. Migration DB

```sql
CREATE TABLE global_sync_jobs (
    id UUID PRIMARY KEY,
    status VARCHAR(20) NOT NULL DEFAULT 'running',
    current_step INT NOT NULL DEFAULT 1,
    current_step_name VARCHAR(50) NOT NULL DEFAULT 'sync_projects',
    step_progress INT NOT NULL DEFAULT 0,
    step_total INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP DEFAULT NULL
);

CREATE INDEX idx_global_sync_status ON global_sync_jobs(status);
```

---

## 6. Tests

### Backend
- GlobalSyncJob entity (create, step transitions, mutex)
- GlobalSyncHandler (orchestration des 3 steps)
- Controller POST /sync (creation + 409 conflict)
- Controller GET /sync/current

### Frontend
- useGlobalSync composable (start, load current, Mercure updates)
- SyncProgressBanner (rendering des 3 etats: idle, running, completed)
- SyncButton (etats repos/running)
