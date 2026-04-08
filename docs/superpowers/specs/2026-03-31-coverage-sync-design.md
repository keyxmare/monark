# Coverage Sync — Design Spec

## Motivation

Monark centralise la gouvernance technique de multiples projets. Le coverage est une metrique cle mais actuellement invisible : certains projets ont une CI qui le calcule, d'autres ont les commandes mais pas le reporting, d'autres rien du tout. On veut recuperer et historiser le coverage de chaque projet, d'abord via les API CI (GitLab/GitHub), puis via des commandes Docker locales dans une future iteration.

## Perimetre

### In scope

- Nouveau bounded context `Coverage` avec entite `CoverageSnapshot`
- Nouveau step `SyncCoverage` dans le Global Sync (Step 2, entre SyncProjects et SyncVersions)
- Strategy pattern pour les providers de coverage (GitLab, GitHub, futur Docker local)
- Historique par commit
- Page `/coverage` avec bilan agrege + liste des projets
- Endpoints API : `GET /api/v1/coverage` et `GET /api/v1/coverage/:projectSlug`

### Out of scope (futures iterations)

- Mode `LocalDockerCoverageProvider` (commandes Docker locales)
- Page drill-down `/coverage/:slug` avec graphe d'evolution par commit
- Seuils configurables par projet
- Notifications (alertes quand le coverage baisse)

## Modele de donnees

### Entite `CoverageSnapshot`

Table : `coverage_snapshots`

| Champ | Type | Contraintes |
|---|---|---|
| id | UUID v7 | PK |
| projectId | UUID | FK `catalog_projects`, NOT NULL, INDEX |
| commitHash | string(40) | NOT NULL |
| coveragePercent | float | NOT NULL |
| source | CoverageSource enum | NOT NULL |
| ref | string(255) | NOT NULL |
| pipelineId | string(255) | nullable |
| createdAt | DateTimeImmutable | NOT NULL |

Index composite : `(projectId, commitHash)` pour les requetes d'historique et deduplication a l'affichage.

### Enum `CoverageSource`

```php
enum CoverageSource: string
{
    case CiGitlab = 'ci_gitlab';
    case CiGithub = 'ci_github';
    case LocalDocker = 'local_docker';

    public static function fromProviderType(ProviderType $type): self
    {
        return match ($type) {
            ProviderType::GitLab => self::CiGitlab,
            ProviderType::GitHub => self::CiGithub,
            ProviderType::Bitbucket => throw new \LogicException('Bitbucket coverage not supported yet.'),
        };
    }
}
```

### Repository

```php
interface CoverageSnapshotRepositoryInterface
{
    public function save(CoverageSnapshot $snapshot): void;
    public function findLatestByProject(Uuid $projectId): ?CoverageSnapshot;
    public function findAllByProject(Uuid $projectId, int $limit = 50): array;
    public function findLatestPerProject(): array;
}
```

## Integration dans le Global Sync

### Step reordering

| Step | Valeur | Nom | Description |
|---|---|---|---|
| SyncProjects | 1 | `sync_projects` | Sync metadata, scan structure |
| **SyncCoverage** | **2** | **`sync_coverage`** | **Fetch coverage depuis CI** |
| SyncVersions | 3 | `sync_versions` | Registries + endoflife.date |
| ScanCve | 4 | `scan_cve` | Stub (futur OSV.dev) |

### Modifications de l'enum `GlobalSyncStep`

```php
enum GlobalSyncStep: int
{
    case SyncProjects = 1;
    case SyncCoverage = 2;
    case SyncVersions = 3;
    case ScanCve = 4;

    public function name(): string
    {
        return match ($this) {
            self::SyncProjects => 'sync_projects',
            self::SyncCoverage => 'sync_coverage',
            self::SyncVersions => 'sync_versions',
            self::ScanCve => 'scan_cve',
        };
    }

    public function next(): ?self
    {
        return match ($this) {
            self::SyncProjects => self::SyncCoverage,
            self::SyncCoverage => self::SyncVersions,
            self::SyncVersions => self::ScanCve,
            self::ScanCve => null,
        };
    }
}
```

### Flow evenementiel

```
Step 1 complete (tous ProjectScannedEvent recus)
  |
GlobalSyncProgressListener.transitionToSyncCoverage()
  -> Compte les projets avec provider non null
  -> startStep(SyncCoverage, countProjectsWithProvider)
  -> Dispatch FetchProjectCoverageCommand x N
  |
Workers traitent en parallele
  -> FetchProjectCoverageHandler
     -> CoverageProviderRegistry.resolve(providerType)
     -> provider.fetchCoverage(project)
     -> Persiste CoverageSnapshot (si result non null)
     -> Dispatch ProjectCoverageFetchedEvent
  |
GlobalSyncCoverageProgressListener
  -> incrementProgress + Mercure
  -> message: "{projectName}: {percent}%" ou "{projectName}: n/a"
  -> Quand termine -> transitionToSyncVersions()
     -> Dispatch SyncDependencyVersionsCommand + SyncProductVersionsCommand
```

### Projets sans provider

Les projets avec `provider = null` sont exclus du step SyncCoverage. Ils ne comptent pas dans le `stepTotal`. Ils seront eligibles uniquement via le futur mode `LocalDockerCoverageProvider`.

### Edge case : aucun projet eligible

Si aucun projet n'a de provider (tous `provider = null`), le step SyncCoverage est skip : `startStep(SyncCoverage, 0)` puis transition immediate vers SyncVersions. Meme pattern que ScanCve qui complete immediatement.

## Strategy Pattern — Coverage Providers

### Interface

```php
interface CoverageProviderInterface
{
    public function supports(ProviderType $type): bool;

    public function fetchCoverage(Project $project): ?CoverageResult;
}
```

### Value Object `CoverageResult`

```php
final readonly class CoverageResult
{
    public function __construct(
        public float $coveragePercent,
        public string $commitHash,
        public string $ref,
        public ?string $pipelineId,
    ) {}
}
```

### `GitLabCoverageProvider`

1. `GET {provider.url}/api/v4/projects/{project.externalId}/pipelines?ref={defaultBranch}&status=success&per_page=1`
   - Header : `PRIVATE-TOKEN: {provider.apiToken}`
2. Extrait `coverage` (float natif GitLab), `sha`, `id` du pipeline
3. Si `coverage` est null (pas configure dans la CI) -> retourne `null`
4. Retourne `CoverageResult(coverage, sha, ref, pipelineId)`

### `GitHubCoverageProvider`

1. `GET {provider.url}/repos/{owner}/{repo}/actions/runs?branch={defaultBranch}&status=success&per_page=1`
   - Header : `Authorization: Bearer {provider.apiToken}`
2. Recupere le `head_sha` du run
3. `GET {provider.url}/repos/{owner}/{repo}/check-runs?head_sha={sha}`
4. Cherche dans les outputs un pattern `coverage:\s*(\d+\.?\d*)%`
5. Si rien trouve -> retourne `null`
6. Retourne `CoverageResult(percent, sha, ref, runId)`

Note : l'extraction `{owner}/{repo}` se fait depuis `project.repositoryUrl`.

### `CoverageProviderRegistry`

Service qui collecte les `CoverageProviderInterface` via un tagged iterator Symfony (`#[AutoconfigureTag('app.coverage_provider')]`). Methode `resolve(ProviderType): ?CoverageProviderInterface`.

### Gestion des erreurs

- API indisponible / timeout -> log warning, retourne `null`
- Pas de coverage dans la CI -> retourne `null`
- Dans les deux cas : le projet est compte comme traite (progress incremente), pas de snapshot cree
- Pas de retry automatique

## Commands, Handlers et Events

### `FetchProjectCoverageCommand`

```php
final readonly class FetchProjectCoverageCommand
{
    public function __construct(
        public string $projectId,
        public string $syncId,
    ) {}
}
```

### `FetchProjectCoverageHandler`

```php
#[AsMessageHandler(bus: 'command.bus')]
final readonly class FetchProjectCoverageHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private CoverageProviderRegistry $providerRegistry,
        private CoverageSnapshotRepositoryInterface $snapshotRepository,
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(FetchProjectCoverageCommand $command): void
    {
        $project = $this->projectRepository->find($command->projectId);
        $provider = $project->getProvider();
        $coveragePercent = null;

        if ($provider !== null) {
            $coverageProvider = $this->providerRegistry->resolve($provider->getType());
            if ($coverageProvider !== null) {
                $result = $coverageProvider->fetchCoverage($project);
                if ($result !== null) {
                    $snapshot = CoverageSnapshot::create(
                        projectId: $project->getId(),
                        commitHash: $result->commitHash,
                        coveragePercent: $result->coveragePercent,
                        source: CoverageSource::fromProviderType($provider->getType()),
                        ref: $result->ref,
                        pipelineId: $result->pipelineId,
                    );
                    $this->snapshotRepository->save($snapshot);
                    $coveragePercent = $result->coveragePercent;
                }
            }
        }

        $this->eventBus->dispatch(new ProjectCoverageFetchedEvent(
            projectId: $command->projectId,
            syncId: $command->syncId,
            projectName: $project->getName(),
            coveragePercent: $coveragePercent,
        ));
    }
}
```

### `ProjectCoverageFetchedEvent`

```php
final readonly class ProjectCoverageFetchedEvent
{
    public function __construct(
        public string $projectId,
        public string $syncId,
        public string $projectName,
        public ?float $coveragePercent,
    ) {}
}
```

### `GlobalSyncCoverageProgressListener`

Meme pattern que `GlobalSyncVersionProgressListener` :
- Ecoute `ProjectCoverageFetchedEvent`
- Verifie step = `sync_coverage`
- `incrementProgress()` + Mercure avec message `"{projectName}: {percent}%"` ou `"{projectName}: n/a"`
- Quand `stepProgress >= stepTotal` -> `transitionToSyncVersions()`

### Modifications des listeners existants

**`GlobalSyncProgressListener`** : la methode `transitionToStep2()` dispatch desormais `FetchProjectCoverageCommand` au lieu de `SyncDependencyVersionsCommand`/`SyncProductVersionsCommand`. Renommer en `transitionToSyncCoverage()`.

**`GlobalSyncVersionProgressListener`** : le step verifie passe de valeur 2 a 3. La transition vers ScanCve passe de step 3 a step 4. Aucun changement de logique.

## Frontend

### Types

```typescript
interface GlobalSyncState {
  syncId: string;
  status: 'running' | 'completed' | 'failed';
  currentStep: 1 | 2 | 3 | 4;
  currentStepName: 'sync_projects' | 'sync_coverage' | 'sync_versions' | 'scan_cve';
  stepProgress: number;
  stepTotal: number;
  completedSteps: SyncStepName[];
  message?: string;
}
```

### `SyncProgressBanner`

Passe de 3 a 4 breadcrumbs :
```typescript
const STEP_LABELS: Record<SyncStepName, string> = {
  sync_projects: 'Sync Projets',
  sync_coverage: 'Sync Coverage',
  sync_versions: 'Sync Versions',
  scan_cve: 'Scan CVE',
};
```

### Page `/coverage`

**Route :** `/coverage` dans le router existant.

**Composants :**

- `CoverageDashboard.vue` — page principale, layout bilan + liste
- `CoverageSummaryCard.vue` — metriques agregees en haut :
  - Moyenne de coverage globale
  - Nombre de projets couverts / total
  - Projets >= 80% vs < 80%
  - Tendance globale (vs snapshot precedent)
- `CoverageProjectList.vue` — tableau des projets :
  - Colonnes : Projet, Coverage (%), Tendance, Source, Dernier commit, Date
  - Barre de couleur : vert >= 80%, orange >= 60%, rouge < 60%
  - Tri par coverage (defaut decroissant), nom, date
  - Projets sans coverage affiches avec "—"
  - Clic sur un projet -> `/coverage/:slug` (route preparee, page placeholder)

### API Endpoints

**`GET /api/v1/coverage`**

Response :
```json
{
  "summary": {
    "averageCoverage": 74.2,
    "totalProjects": 15,
    "coveredProjects": 10,
    "aboveThreshold": 7,
    "belowThreshold": 3,
    "trend": +1.3
  },
  "projects": [
    {
      "projectId": "uuid",
      "projectName": "back-api",
      "projectSlug": "back-api",
      "coveragePercent": 82.3,
      "trend": +2.1,
      "source": "ci_gitlab",
      "commitHash": "a3f21bc",
      "ref": "main",
      "syncedAt": "2026-03-31T14:30:00Z"
    }
  ]
}
```

La tendance est calculee en comparant le dernier snapshot avec l'avant-dernier pour chaque projet.

**`GET /api/v1/coverage/:projectSlug`** (prepare, pas consomme dans cette iteration)

Response :
```json
{
  "project": { "id": "uuid", "name": "back-api", "slug": "back-api" },
  "snapshots": [
    {
      "commitHash": "a3f21bc",
      "coveragePercent": 82.3,
      "source": "ci_gitlab",
      "ref": "main",
      "pipelineId": "12345",
      "createdAt": "2026-03-31T14:30:00Z"
    }
  ]
}
```

## Migration

Une migration Doctrine pour creer la table `coverage_snapshots` avec les index :
- PK sur `id`
- Index sur `projectId`
- Index composite `(projectId, commitHash)`
- FK vers `catalog_projects(id)` ON DELETE CASCADE

## Preparation du mode Local Docker (futur)

L'architecture est prete pour accueillir un `LocalDockerCoverageProvider` :
- Implemente `CoverageProviderInterface`
- Pas base sur le `ProviderType` (pas un provider CI) — resolu differemment (config par projet ou fallback quand pas de provider)
- Executera des commandes Docker pour generer un rapport clover, le parsera, et retournera un `CoverageResult`
- Le registry devra evoluer pour supporter un fallback local en plus de la resolution par `ProviderType`

Aucun code a ecrire maintenant — juste la conscience que le `CoverageProviderRegistry` devra accepter un 2eme mode de resolution.
