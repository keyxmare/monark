# Dashboard — Évolution des mises à jour de dépendances

**Date** : 2026-04-06
**Statut** : design validé
**Contexte** : refonte du `DashboardPage` actuellement stub

## Objectif

Donner à l'utilisateur une vue d'overview sur l'état "à jour / pas à jour" du catalogue de dépendances, et son évolution dans le temps. Le dashboard remplit le `GET /api/v1/activity/dashboard` aujourd'hui stub (`metrics: []`).

## Décisions de scope

| Décision | Choix |
|---|---|
| Granularité | Vue **globale** (catalogue entier) + breakdown des **5 derniers projets synchronisés** |
| Métrique principale | Ratio `% à jour` (courbe principale) + total de deps suivies (sparkline secondaire) |
| Plage de temps | **Sélecteur dynamique** : 7j / 30j / 90j / 1y |
| Trigger des snapshots | Fin de chaque global sync (event-driven) |
| KPI cards | 4 cards avec **delta vs snapshot précédent** |
| Breakdown projets | 5 derniers synchronisés, colonne `Δ` colorée |
| Lib graphique | **SVG fait main**, zéro dépendance ajoutée |

## Hors scope (future work)

- **Backfill historique via commit walker** — fera l'objet d'une spec séparée. Implique d'ajouter `listCommits` à `GitProviderInterface`, de walker l'historique git de chaque projet, de re-parser les manifests à chaque commit, et de croiser avec les release dates des registries. Trop gros pour ce dashboard.
- **Live update via Mercure** — pas de push temps réel. L'utilisateur fait F5 ou attend le prochain chargement.
- **Breakdown par package manager / par type de dépendance** — pas dans le scope. Le snapshot reste agrégé.
- **E2E Playwright** — couverture par tests unitaires + functional API. Un E2E pourra être ajouté plus tard.

---

## Architecture backend

### Bounded context

Tout vit dans `Activity` — le contexte qui agrège déjà les métriques cross-context (cf. `BuildMetric` qui agrège Coverage et Sync). `GetDashboardHandler` y est déjà.

### Nouvelles entities

#### `App\Activity\Domain\Model\DependencyStatsSnapshot`

Snapshot global du catalogue à un instant T.

```
id                  uuid (pk)
total_count         int
up_to_date_count    int
outdated_count      int
vulnerability_count int
created_at          timestamp(0)
```

Index sur `created_at` (toutes les queries sont des range queries chronologiques).
Snapshots **immutables**. Le delta est calculé à la lecture, jamais stocké.

#### `App\Activity\Domain\Model\ProjectDependencyStatsSnapshot`

Snapshot par projet à un instant T.

```
id                  uuid (pk)
project_id          uuid (fk → projects.id)
total_count         int
up_to_date_count    int
outdated_count      int
vulnerability_count int
created_at          timestamp(0)
```

Index sur `(project_id, created_at DESC)` pour récupérer rapidement le dernier snapshot d'un projet.

### Repositories

#### `DependencyStatsSnapshotRepositoryInterface`

```php
public function save(DependencyStatsSnapshot $snapshot): void;
public function findLatest(): ?DependencyStatsSnapshot;
public function findPrevious(): ?DependencyStatsSnapshot;
public function findInRange(DateTimeImmutable $from, DateTimeImmutable $to): array;
```

#### `ProjectDependencyStatsSnapshotRepositoryInterface`

```php
public function save(ProjectDependencyStatsSnapshot $snapshot): void;
public function findLatestForProject(Uuid $projectId): ?ProjectDependencyStatsSnapshot;
public function findPreviousForProject(Uuid $projectId): ?ProjectDependencyStatsSnapshot;

/** @param list<Uuid> $projectIds @return array<string, ProjectDependencyStatsSnapshot> */
public function findLatestForProjects(array $projectIds): array;

/** @param list<Uuid> $projectIds @return array<string, ProjectDependencyStatsSnapshot> */
public function findPreviousForProjects(array $projectIds): array;
```

Implémentations Doctrine dans `App\Activity\Infrastructure\Persistence\Doctrine\`.

### Snapshot creation (event-driven)

#### Snapshot global

**Event** : `App\Sync\Domain\Event\GlobalSyncJobCompletedEvent` (à créer si pas déjà existant ; identifier précisément le handler qui marque le job complété pour y dispatcher l'event).

**Listener** : `App\Activity\Application\EventListener\CaptureDependencyStatsSnapshotListener`

Comportement :
1. Appelle `DependencyRepositoryInterface::getStats([])` (déjà existant, retourne `{total, outdated, totalVulnerabilities}`)
2. Calcule `up_to_date = total - outdated`
3. Persiste un nouveau `DependencyStatsSnapshot`
4. **try/catch** englobant : conformément au CLAUDE.md, un échec de persistance ne doit pas casser le pipeline parent. Logger l'erreur, continuer.

#### Snapshot par projet

**Event** : événement de fin de sync de dépendances **par projet**. À identifier précisément à l'implémentation — probablement émis depuis `SyncDependencyVersionsHandler` à la fin du pipeline. Si l'event n'existe pas, le créer (`DependencyVersionsSyncedForProjectEvent` avec `projectId`).

**Listener** : `App\Activity\Application\EventListener\CaptureProjectDependencyStatsSnapshotListener`

Comportement :
1. Appelle `getStats(['projectId' => $projectId])`
2. Persiste `ProjectDependencyStatsSnapshot`
3. try/catch idem

### Query du dashboard

#### `GetDashboardQuery` (étendu)

```php
final readonly class GetDashboardQuery
{
    public function __construct(
        public string $userId,
        public string $range = '30d',  // '7d' | '30d' | '90d' | '1y'
    ) {}
}
```

#### `DashboardOutput` (étendu)

```php
final readonly class DashboardOutput
{
    public function __construct(
        /** @var list<DashboardMetric> */            public array $metrics,
        /** @var list<DashboardSnapshotPoint> */     public array $history,
        /** @var list<DashboardRecentProject> */     public array $recentProjects,
    ) {}
}
```

#### Sub-DTOs

```php
final readonly class DashboardSnapshotPoint
{
    public function __construct(
        public string $timestamp,        // ISO-8601
        public int $totalCount,
        public int $upToDateCount,
        public int $outdatedCount,
        public float $upToDateRatio,     // 0..100, pré-calculé serveur
    ) {}
}

final readonly class DashboardRecentProject
{
    public function __construct(
        public string $projectId,
        public string $name,
        public string $slug,
        public ?string $lastSyncedAt,    // ISO-8601 ou null
        public int $totalDependencies,
        public int $upToDateCount,
        public int $outdatedCount,
        public float $upToDateRatio,
        public int $deltaSinceLastSync,  // signé, 0 si pas de previous snapshot
    ) {}
}
```

#### `GetDashboardHandler` enrichi

1. **KPI cards (4)** :
   - `getStats([])` → totaux courants
   - `findLatest()` + `findPrevious()` sur le repo de snapshot global
   - Pour chaque card, `change = ((current - previous) / previous) * 100` si `previous != null`, sinon `null`
   - Cards : `Total dépendances`, `À jour`, `Obsolètes`, `Vulnérabilités`
   - Le label `Vulnérabilités` est traité côté front comme "delta inversé" : un `+` est mauvais

2. **History** :
   - Calcule `from` à partir de `range` (30d → -30 jours)
   - `findInRange(from, now)` → liste de snapshots
   - Mappe vers `DashboardSnapshotPoint`, calcule `upToDateRatio = upToDateCount / totalCount * 100` (avec garde `total > 0`)
   - **Downsampling** : si `range ∈ {90d, 1y}`, on garde le **dernier snapshot de chaque jour** (regroupement par date civile, on prend le `created_at` max). Pour `7d` et `30d`, on conserve tous les points.

3. **Recent projects** :
   - Nouveau `ProjectRepositoryInterface::findRecentlySynced(int $limit = 5)` retournant les 5 projets triés par `lastSyncedAt DESC`. Les projets jamais synchronisés sont exclus.
   - `findLatestForProjects([...])` + `findPreviousForProjects([...])` en batch (2 queries totales pour les 5 projets)
   - Pour chaque projet, calcule le delta : `latest.upToDateCount - previous.upToDateCount` (si `previous == null` → `0`)
   - Mappe vers `DashboardRecentProject`

### Controller

`GET /api/v1/activity/dashboard?range=30d` — déjà existant, on ajoute juste la lecture du query param `range` et son passage au query.

Validation : `range` doit être dans `{'7d','30d','90d','1y'}`. Si absent ou invalide, fallback sur `30d`.

### Migration Doctrine

Une migration unique qui crée les deux tables `dependency_stats_snapshots` et `project_dependency_stats_snapshots` avec leurs index et la FK sur `projects`.

---

## Architecture frontend

### Layout (validé visuellement, Option B "two-column desktop")

```
+----------------------------------------------------------+
| Tableau de bord                Dernier sync il y a 2 min |
+----------------------------------------------------------+
| KPI 1     | KPI 2     | KPI 3     | KPI 4               |
| 1 247     | 1 089     | 158       | 12                  |
| +24 ↑     | +18 ↑     | -6 ↓      | +2 ↑ (red)          |
+----------------------------------------------------------+
| Évolution           [7j 30j 90j 1y]  | Récents          |
|                                       |                  |
|        ╱╲                             | proj-1   +3 ↑    |
|       ╱  ╲╱╲                          | proj-2   +5 ↑    |
|      ╱      ╲╱                        | proj-3   -2 ↓    |
|                                       | proj-4    —      |
|---------------------------------------|                  |
| Total deps              ↑ +52         | proj-5   +8 ↑    |
|        ───────                        |                  |
+----------------------------------------------------------+
```

KPI cards en haut full-width. En dessous, une grille 2 colonnes (`2fr 1fr`) :
- Colonne gauche : panel "Évolution" (chart principal + range selector) puis panel "Total" (sparkline).
- Colonne droite : panel "Récemment synchronisés" avec 5 cards de projet.

Mobile : la grille collapse en 1 colonne (chart au-dessus, projets en dessous).

### Arborescence des fichiers

```
frontend/src/activity/
├── pages/
│   └── DashboardPage.vue                  (refait)
├── components/
│   ├── DashboardKpiCard.vue
│   ├── DashboardEvolutionChart.vue
│   ├── DashboardTotalSparkline.vue
│   └── DashboardRecentProjectsList.vue
├── stores/
│   └── dashboard.ts                       (étendu)
├── services/
│   └── dashboard.service.ts               (étendu)
└── types/
    └── dashboard.types.ts                 (nouveau)
```

### Composants

#### `DashboardKpiCard.vue`

```typescript
defineProps<{
  label: string
  value: number | string
  delta?: number | null
  deltaInverted?: boolean   // true pour Vulnérabilités : un + est mauvais
}>()
```

Affiche : label en haut, value en gros, et si `delta != null` une flèche colorée verte/rouge selon le sens (et `deltaInverted`).

#### `DashboardEvolutionChart.vue`

```typescript
defineProps<{
  points: DashboardSnapshotPoint[]
  range: '7d' | '30d' | '90d' | '1y'
}>()
defineEmits<{ 'update:range': [range: '7d' | '30d' | '90d' | '1y'] }>()
```

Rendu SVG à la main :
- Composable interne `useSvgChart(points, width, height)` qui calcule le `path d="..."` du tracé + de l'aire, les axes Y (graduations 100/90/80/70%), les labels X (dates en fonction du range).
- Range selector : 4 boutons, classe `.active` sur le sélectionné, click émet `update:range`.
- Tooltip : sur `mousemove` du chart, calcule l'index du point le plus proche en X, affiche un overlay positionné absolument avec date + ratio + détail "1 062 / 1 245 à jour".
- Chart vide (0 ou 1 point) : affiche un état "Pas assez de données pour afficher l'évolution. Lancez un sync."

#### `DashboardTotalSparkline.vue`

```typescript
defineProps<{ points: DashboardSnapshotPoint[] }>()
```

Plus simple : calcule le `path d="..."` à partir de `point.totalCount`, affiche la valeur courante (`points[points.length - 1].totalCount`) et la tendance globale (`current - first`). Pas de tooltip.

#### `DashboardRecentProjectsList.vue`

```typescript
defineProps<{ projects: DashboardRecentProject[] }>()
```

Render 5 cards verticales :
- Nom + slug en monospace en haut
- "il y a X" à droite
- Ratio bar coloré (vert ≥85%, jaune 70-85%, rouge <70%) + pourcentage
- Delta avec flèche colorée (vert positif, rouge négatif, gris zéro)

Click sur une card → `router.push({ name: 'project-detail', params: { slug } })`.

### Store `dashboard.ts` (étendu)

```typescript
export const useDashboardStore = defineStore('dashboard', () => {
  const metrics = ref<DashboardMetric[]>([])
  const history = ref<DashboardSnapshotPoint[]>([])
  const recentProjects = ref<DashboardRecentProject[]>([])
  const range = ref<DashboardRange>('30d')
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function load(newRange?: DashboardRange): Promise<void> {
    if (newRange) range.value = newRange
    loading.value = true
    try {
      const res = await dashboardService.getDashboard(range.value)
      metrics.value = res.data.metrics
      history.value = res.data.history
      recentProjects.value = res.data.recentProjects
      error.value = null
    } catch (e) {
      error.value = (e as Error).message
    } finally {
      loading.value = false
    }
  }

  async function setRange(newRange: DashboardRange): Promise<void> {
    range.value = newRange
    await load()  // refetch tout, simple et suffisant
  }

  return { metrics, history, recentProjects, range, loading, error, load, setRange }
})
```

Décision : `setRange` refetch tout (pas seulement l'history) — c'est plus simple, et le call est rapide. Optimisation prématurée évitée.

### Service `dashboard.service.ts` (étendu)

```typescript
export const dashboardService = {
  getDashboard(range: DashboardRange = '30d'): Promise<ApiResponse<DashboardData>> {
    return api.get<ApiResponse<DashboardData>>(`/activity/dashboard?range=${range}`)
  },
}
```

### Types `dashboard.types.ts` (nouveau)

Interfaces TS qui matchent 1:1 les DTOs PHP : `DashboardMetric`, `DashboardSnapshotPoint`, `DashboardRecentProject`, `DashboardData`, `DashboardRange`.

### `DashboardPage.vue` (refait)

Orchestration uniquement : `onMounted → store.load()`, distribue les données aux 4 sous-composants. Pas de logique métier.

---

## Tests

### Backend (Pest)

| Couche | Test |
|---|---|
| Domain | `DependencyStatsSnapshotTest` — création, getters |
| Domain | `ProjectDependencyStatsSnapshotTest` — idem |
| Application | `CaptureDependencyStatsSnapshotListenerTest` — sur réception de l'event, persiste un snapshot avec les bons compteurs ; en cas d'exception du repo, ne fait pas remonter (try/catch) |
| Application | `CaptureProjectDependencyStatsSnapshotListenerTest` — idem côté projet |
| Application | `GetDashboardHandlerTest` — couvre : aucun snapshot (delta `null`), un seul snapshot (delta `null`), N snapshots (delta calculé), range filtering, downsampling 90d/1y, recent projects avec/sans previous snapshot |
| Infrastructure | `DoctrineDependencyStatsSnapshotRepositoryTest` — `save`, `findLatest`, `findPrevious`, `findInRange` |
| Infrastructure | `DoctrineProjectDependencyStatsSnapshotRepositoryTest` — idem + `findLatestForProjects` et `findPreviousForProjects` (batch) |
| Functional | `GetDashboardEndpointTest` — `GET /api/v1/activity/dashboard?range=30d` retourne 200 + structure DTO valide, requiert auth, valide le param `range` |

Stubs Application : **anonymous classes** implémentant les interfaces, conformément au CLAUDE.md.

### Frontend (Vitest)

| Composant | Test |
|---|---|
| `DashboardKpiCard.test.ts` | Rendu label/value, flèche verte si delta positif normal, rouge si négatif normal, inversion correcte avec `deltaInverted=true`, pas de flèche si delta null |
| `DashboardEvolutionChart.test.ts` | Génère le bon path SVG pour des points donnés, range buttons émettent `update:range`, tooltip apparaît au mouseover et change selon le point hovered, état vide quand 0/1 point |
| `DashboardTotalSparkline.test.ts` | Path SVG correct, affiche la tendance |
| `DashboardRecentProjectsList.test.ts` | 5 cards, classes CSS selon le ratio (good/warn/bad), delta avec couleur, click → router.push |
| `dashboard.store.test.ts` (étendu) | `load()` peuple metrics/history/recentProjects, `setRange()` change le range et refetch |
| `dashboard.service.test.ts` (étendu) | URL avec query param `range` |

### Vérifications post-implémentation

Conformément au CLAUDE.md, avant chaque commit :

```
make fix-backend
make lint-backend       # cs-fixer + PHPStan, 0 erreur
make test-backend       # Pest, 0 failure
make lint-frontend      # ESLint + Prettier, 0 erreur
make test-frontend      # Vitest, 0 failure
```

---

## Risques et mitigations

| Risque | Mitigation |
|---|---|
| `getStats([])` lent (cache 300s) ralentit la fin du global sync | Acceptable, c'est en fin de pipeline, pas dans la hot path |
| Listener qui crash casse le sync | try/catch obligatoire dans le listener (pattern Mercure CLAUDE.md) |
| Premiers jours sans snapshot → graphe vide | Composant chart gère l'état vide explicitement |
| Snapshots multiples le même jour pollu(ant) le 90d/1y | Downsampling côté query handler |
| Renommage de l'event de fin de sync | Identifier précisément le handler à l'implé, créer l'event si manquant |
| Migration sur DB existante | Tables nouvelles, pas de modif sur l'existant, migration safe |

---

## Critères de succès

1. Le `GET /api/v1/activity/dashboard?range=30d` retourne un payload conforme aux DTOs définis ci-dessus.
2. Après un global sync, un snapshot global est créé en DB.
3. Après un sync de deps d'un projet, un snapshot par-projet est créé en DB.
4. La page `/dashboard` affiche les 4 KPI cards, le chart d'évolution avec range selector fonctionnel, le sparkline du total, et les 5 derniers projets synchronisés.
5. Le chart répond au changement de range et au hover (tooltip).
6. Tous les tests Pest + Vitest passent ; lint backend et frontend verts.
