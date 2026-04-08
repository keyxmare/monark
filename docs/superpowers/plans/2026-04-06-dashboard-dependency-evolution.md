# Dashboard — Dependency Update Evolution Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a dashboard showing the global up-to-date / outdated state of catalog dependencies, its evolution over time, and the 5 most recently synchronized projects with their delta.

**Architecture:** Two new immutable snapshot entities in the `Activity` bounded context (one global, one per-project), captured event-driven at the end of each global sync (global snapshot) and at the end of each `ProjectScannedEvent` (per-project snapshot). The existing `GetDashboardHandler` is enriched to expose 4 KPI cards with deltas, an evolution time-series, and a recent-projects breakdown. The frontend `DashboardPage` is rebuilt with hand-rolled SVG charts (zero new chart libraries) following a 2-column desktop layout.

**Tech Stack:** PHP 8.4 / Symfony 8 / Doctrine ORM / Pest. Vue 3 / TypeScript / Pinia / Vitest. All commands run inside Docker via `make`.

**Spec:** `docs/superpowers/specs/2026-04-06-dashboard-dependency-evolution-design.md`

---

## File map

### Backend — files to create

| Path | Responsibility |
|---|---|
| `backend/src/Activity/Domain/Model/DependencyStatsSnapshot.php` | Global snapshot entity |
| `backend/src/Activity/Domain/Model/ProjectDependencyStatsSnapshot.php` | Per-project snapshot entity |
| `backend/src/Activity/Domain/Repository/DependencyStatsSnapshotRepositoryInterface.php` | Global snapshot repo port |
| `backend/src/Activity/Domain/Repository/ProjectDependencyStatsSnapshotRepositoryInterface.php` | Per-project snapshot repo port |
| `backend/src/Activity/Infrastructure/Persistence/Doctrine/DoctrineDependencyStatsSnapshotRepository.php` | Doctrine impl global |
| `backend/src/Activity/Infrastructure/Persistence/Doctrine/DoctrineProjectDependencyStatsSnapshotRepository.php` | Doctrine impl per-project |
| `backend/src/Activity/Application/EventListener/CaptureDependencyStatsSnapshotListener.php` | Captures global snapshot |
| `backend/src/Activity/Application/EventListener/CaptureProjectDependencyStatsSnapshotListener.php` | Captures per-project snapshot |
| `backend/src/Activity/Application/DTO/DashboardSnapshotPoint.php` | History point DTO |
| `backend/src/Activity/Application/DTO/DashboardRecentProject.php` | Recent project DTO |
| `backend/src/Sync/Domain/Event/GlobalSyncJobCompletedEvent.php` | New event |
| `backend/migrations/Version<TIMESTAMP>.php` | Migration creating both tables |

### Backend — files to modify

| Path | Change |
|---|---|
| `backend/src/Activity/Application/DTO/DashboardOutput.php` | Add `history` and `recentProjects` fields |
| `backend/src/Activity/Application/Query/GetDashboardQuery.php` | Add `range` parameter |
| `backend/src/Activity/Application/QueryHandler/GetDashboardHandler.php` | Full rewrite |
| `backend/src/Activity/Presentation/Controller/GetDashboardController.php` | Read `range` query param |
| `backend/src/Catalog/Application/CommandHandler/ScanProjectHandler.php` | Call `$project->markSynced()` after success |
| `backend/src/Catalog/Domain/Repository/ProjectRepositoryInterface.php` | Add `findRecentlySynced(int $limit = 5)` |
| `backend/src/Catalog/Infrastructure/Persistence/Doctrine/DoctrineProjectRepository.php` | Implement `findRecentlySynced` |
| `backend/src/Sync/Application/CommandHandler/GlobalSyncHandler.php` | Dispatch `GlobalSyncJobCompletedEvent` after `complete()` |
| `backend/src/Sync/Application/EventListener/GlobalSyncVersionProgressListener.php` | Dispatch `GlobalSyncJobCompletedEvent` after `complete()` |

### Frontend — files to create

| Path | Responsibility |
|---|---|
| `frontend/src/activity/types/dashboard.types.ts` | TS interfaces matching backend DTOs |
| `frontend/src/activity/components/DashboardKpiCard.vue` | Single KPI card |
| `frontend/src/activity/components/DashboardEvolutionChart.vue` | Main SVG chart + range selector |
| `frontend/src/activity/components/DashboardTotalSparkline.vue` | SVG sparkline |
| `frontend/src/activity/components/DashboardRecentProjectsList.vue` | 5 recent project cards |

### Frontend — files to modify

| Path | Change |
|---|---|
| `frontend/src/activity/services/dashboard.service.ts` | Add `range` param + new types |
| `frontend/src/activity/stores/dashboard.ts` | Add `history`, `recentProjects`, `range`, `setRange` |
| `frontend/src/activity/pages/DashboardPage.vue` | Refactor to compose new components |
| `frontend/src/locales/fr.json` and `en.json` | New translation keys |

---

## Conventions reminders

- All commands run via `make` or `docker compose ... exec -T backend|frontend`. **Never** call php/composer/pnpm directly on host.
- Backend tests: Pest. Stubs use **anonymous classes**, not Mockery.
- After every code change, run the verification suite from CLAUDE.md (`make fix-backend && make lint-backend && make test-backend && make lint-frontend && make test-frontend`).
- Commit at the end of every task. Use `chore` / `feat` / `test` / `refactor` per Conventional Commits.
- After ANY backend change, restart the messenger consumer: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml restart messenger-consumer`.

---

## Task 1: `DependencyStatsSnapshot` domain entity

**Files:**
- Create: `backend/src/Activity/Domain/Model/DependencyStatsSnapshot.php`
- Test: `backend/tests/Unit/Activity/Domain/DependencyStatsSnapshotTest.php`

- [ ] **Step 1: Write the failing test**

Create `backend/tests/Unit/Activity/Domain/DependencyStatsSnapshotTest.php`:

```php
<?php

declare(strict_types=1);

use App\Activity\Domain\Model\DependencyStatsSnapshot;
use Symfony\Component\Uid\Uuid;

describe('DependencyStatsSnapshot', function () {
    it('creates with all counters', function () {
        $snapshot = DependencyStatsSnapshot::create(
            totalCount: 1247,
            upToDateCount: 1089,
            outdatedCount: 158,
            vulnerabilityCount: 12,
        );

        expect($snapshot->getId())->toBeInstanceOf(Uuid::class);
        expect($snapshot->getTotalCount())->toBe(1247);
        expect($snapshot->getUpToDateCount())->toBe(1089);
        expect($snapshot->getOutdatedCount())->toBe(158);
        expect($snapshot->getVulnerabilityCount())->toBe(12);
        expect($snapshot->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('creates with zero values', function () {
        $snapshot = DependencyStatsSnapshot::create(
            totalCount: 0,
            upToDateCount: 0,
            outdatedCount: 0,
            vulnerabilityCount: 0,
        );

        expect($snapshot->getTotalCount())->toBe(0);
        expect($snapshot->getUpToDateCount())->toBe(0);
        expect($snapshot->getOutdatedCount())->toBe(0);
        expect($snapshot->getVulnerabilityCount())->toBe(0);
    });
});
```

- [ ] **Step 2: Run test, verify failure**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Activity/Domain/DependencyStatsSnapshotTest.php
```

Expected: FAIL, "Class App\Activity\Domain\Model\DependencyStatsSnapshot not found".

- [ ] **Step 3: Create the entity**

Create `backend/src/Activity/Domain/Model/DependencyStatsSnapshot.php`:

```php
<?php

declare(strict_types=1);

namespace App\Activity\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'activity_dependency_stats_snapshots')]
#[ORM\Index(columns: ['created_at'], name: 'idx_dep_stats_snapshot_created_at')]
final class DependencyStatsSnapshot
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'integer')]
    private int $totalCount;

    #[ORM\Column(type: 'integer')]
    private int $upToDateCount;

    #[ORM\Column(type: 'integer')]
    private int $outdatedCount;

    #[ORM\Column(type: 'integer')]
    private int $vulnerabilityCount;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    private function __construct(
        Uuid $id,
        int $totalCount,
        int $upToDateCount,
        int $outdatedCount,
        int $vulnerabilityCount,
    ) {
        $this->id = $id;
        $this->totalCount = $totalCount;
        $this->upToDateCount = $upToDateCount;
        $this->outdatedCount = $outdatedCount;
        $this->vulnerabilityCount = $vulnerabilityCount;
        $this->createdAt = new DateTimeImmutable();
    }

    public static function create(
        int $totalCount,
        int $upToDateCount,
        int $outdatedCount,
        int $vulnerabilityCount,
    ): self {
        return new self(
            id: Uuid::v7(),
            totalCount: $totalCount,
            upToDateCount: $upToDateCount,
            outdatedCount: $outdatedCount,
            vulnerabilityCount: $vulnerabilityCount,
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getUpToDateCount(): int
    {
        return $this->upToDateCount;
    }

    public function getOutdatedCount(): int
    {
        return $this->outdatedCount;
    }

    public function getVulnerabilityCount(): int
    {
        return $this->vulnerabilityCount;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
```

- [ ] **Step 4: Run test, verify pass**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Activity/Domain/DependencyStatsSnapshotTest.php
```

Expected: PASS (2 tests).

- [ ] **Step 5: Lint + commit**

```
make fix-backend && make lint-backend
git add backend/src/Activity/Domain/Model/DependencyStatsSnapshot.php backend/tests/Unit/Activity/Domain/DependencyStatsSnapshotTest.php
git commit -m "feat(activity): add DependencyStatsSnapshot domain model"
```

---

## Task 2: `ProjectDependencyStatsSnapshot` domain entity

**Files:**
- Create: `backend/src/Activity/Domain/Model/ProjectDependencyStatsSnapshot.php`
- Test: `backend/tests/Unit/Activity/Domain/ProjectDependencyStatsSnapshotTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Activity\Domain\Model\ProjectDependencyStatsSnapshot;
use Symfony\Component\Uid\Uuid;

describe('ProjectDependencyStatsSnapshot', function () {
    it('creates with all counters and project id', function () {
        $projectId = Uuid::v7();
        $snapshot = ProjectDependencyStatsSnapshot::create(
            projectId: $projectId,
            totalCount: 156,
            upToDateCount: 144,
            outdatedCount: 12,
            vulnerabilityCount: 1,
        );

        expect($snapshot->getId())->toBeInstanceOf(Uuid::class);
        expect($snapshot->getProjectId()->equals($projectId))->toBeTrue();
        expect($snapshot->getTotalCount())->toBe(156);
        expect($snapshot->getUpToDateCount())->toBe(144);
        expect($snapshot->getOutdatedCount())->toBe(12);
        expect($snapshot->getVulnerabilityCount())->toBe(1);
        expect($snapshot->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });
});
```

- [ ] **Step 2: Run test, verify failure**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Activity/Domain/ProjectDependencyStatsSnapshotTest.php
```

Expected: FAIL, class not found.

- [ ] **Step 3: Create the entity**

```php
<?php

declare(strict_types=1);

namespace App\Activity\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'activity_project_dependency_stats_snapshots')]
#[ORM\Index(columns: ['project_id', 'created_at'], name: 'idx_proj_dep_stats_snapshot_proj_date')]
final class ProjectDependencyStatsSnapshot
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'uuid')]
    private Uuid $projectId;

    #[ORM\Column(type: 'integer')]
    private int $totalCount;

    #[ORM\Column(type: 'integer')]
    private int $upToDateCount;

    #[ORM\Column(type: 'integer')]
    private int $outdatedCount;

    #[ORM\Column(type: 'integer')]
    private int $vulnerabilityCount;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    private function __construct(
        Uuid $id,
        Uuid $projectId,
        int $totalCount,
        int $upToDateCount,
        int $outdatedCount,
        int $vulnerabilityCount,
    ) {
        $this->id = $id;
        $this->projectId = $projectId;
        $this->totalCount = $totalCount;
        $this->upToDateCount = $upToDateCount;
        $this->outdatedCount = $outdatedCount;
        $this->vulnerabilityCount = $vulnerabilityCount;
        $this->createdAt = new DateTimeImmutable();
    }

    public static function create(
        Uuid $projectId,
        int $totalCount,
        int $upToDateCount,
        int $outdatedCount,
        int $vulnerabilityCount,
    ): self {
        return new self(
            id: Uuid::v7(),
            projectId: $projectId,
            totalCount: $totalCount,
            upToDateCount: $upToDateCount,
            outdatedCount: $outdatedCount,
            vulnerabilityCount: $vulnerabilityCount,
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getProjectId(): Uuid
    {
        return $this->projectId;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getUpToDateCount(): int
    {
        return $this->upToDateCount;
    }

    public function getOutdatedCount(): int
    {
        return $this->outdatedCount;
    }

    public function getVulnerabilityCount(): int
    {
        return $this->vulnerabilityCount;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
```

- [ ] **Step 4: Run test, verify pass**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Activity/Domain/ProjectDependencyStatsSnapshotTest.php
```

Expected: PASS.

- [ ] **Step 5: Lint + commit**

```
make fix-backend && make lint-backend
git add backend/src/Activity/Domain/Model/ProjectDependencyStatsSnapshot.php backend/tests/Unit/Activity/Domain/ProjectDependencyStatsSnapshotTest.php
git commit -m "feat(activity): add ProjectDependencyStatsSnapshot domain model"
```

---

## Task 3: Repository interfaces

**Files:**
- Create: `backend/src/Activity/Domain/Repository/DependencyStatsSnapshotRepositoryInterface.php`
- Create: `backend/src/Activity/Domain/Repository/ProjectDependencyStatsSnapshotRepositoryInterface.php`

No tests for interfaces alone — they're contracts. Tests come with the Doctrine implementations.

- [ ] **Step 1: Create the global repo interface**

```php
<?php

declare(strict_types=1);

namespace App\Activity\Domain\Repository;

use App\Activity\Domain\Model\DependencyStatsSnapshot;
use DateTimeImmutable;

interface DependencyStatsSnapshotRepositoryInterface
{
    public function save(DependencyStatsSnapshot $snapshot): void;

    public function findLatest(): ?DependencyStatsSnapshot;

    public function findPrevious(): ?DependencyStatsSnapshot;

    /** @return list<DependencyStatsSnapshot> */
    public function findInRange(DateTimeImmutable $from, DateTimeImmutable $to): array;
}
```

- [ ] **Step 2: Create the per-project repo interface**

```php
<?php

declare(strict_types=1);

namespace App\Activity\Domain\Repository;

use App\Activity\Domain\Model\ProjectDependencyStatsSnapshot;
use Symfony\Component\Uid\Uuid;

interface ProjectDependencyStatsSnapshotRepositoryInterface
{
    public function save(ProjectDependencyStatsSnapshot $snapshot): void;

    public function findLatestForProject(Uuid $projectId): ?ProjectDependencyStatsSnapshot;

    public function findPreviousForProject(Uuid $projectId): ?ProjectDependencyStatsSnapshot;

    /**
     * @param list<Uuid> $projectIds
     * @return array<string, ProjectDependencyStatsSnapshot> indexed by projectId rfc4122
     */
    public function findLatestForProjects(array $projectIds): array;

    /**
     * @param list<Uuid> $projectIds
     * @return array<string, ProjectDependencyStatsSnapshot> indexed by projectId rfc4122
     */
    public function findPreviousForProjects(array $projectIds): array;
}
```

- [ ] **Step 3: Lint + commit**

```
make fix-backend && make lint-backend
git add backend/src/Activity/Domain/Repository/DependencyStatsSnapshotRepositoryInterface.php backend/src/Activity/Domain/Repository/ProjectDependencyStatsSnapshotRepositoryInterface.php
git commit -m "feat(activity): add stats snapshot repository interfaces"
```

---

## Task 4: Doctrine migration

**Files:**
- Create: `backend/migrations/Version<TIMESTAMP>.php` (filename generated by `make migration`)

- [ ] **Step 1: Generate the migration via Doctrine diff**

```
make migration
```

Expected: a new file `backend/migrations/VersionYYYYMMDDhhmmss.php` containing CREATE TABLE for both `activity_dependency_stats_snapshots` and `activity_project_dependency_stats_snapshots`.

- [ ] **Step 2: Verify the migration content**

Open the generated file and confirm `up()` contains:

- `CREATE TABLE activity_dependency_stats_snapshots (id UUID NOT NULL, total_count INT NOT NULL, up_to_date_count INT NOT NULL, outdated_count INT NOT NULL, vulnerability_count INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))`
- `CREATE INDEX idx_dep_stats_snapshot_created_at ON activity_dependency_stats_snapshots (created_at)`
- `CREATE TABLE activity_project_dependency_stats_snapshots (id UUID NOT NULL, project_id UUID NOT NULL, total_count INT NOT NULL, up_to_date_count INT NOT NULL, outdated_count INT NOT NULL, vulnerability_count INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))`
- `CREATE INDEX idx_proj_dep_stats_snapshot_proj_date ON activity_project_dependency_stats_snapshots (project_id, created_at)`

If anything else is in the diff (modification of unrelated tables), delete the migration file and investigate — the diff is dirty.

- [ ] **Step 3: Run the migration**

```
make migrate
```

Expected: `Successfully migrated to version: ...`.

- [ ] **Step 4: Apply on test DB too**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T -e DATABASE_URL='postgresql://app:changeme@database:5432/monark_test?serverVersion=17&charset=utf8' backend php bin/console doctrine:migrations:migrate --no-interaction --env=test
```

Expected: `Successfully migrated`.

- [ ] **Step 5: Commit**

```
git add backend/migrations/Version*.php
git commit -m "feat(activity): migration for dependency stats snapshots"
```

---

## Task 5: `DoctrineDependencyStatsSnapshotRepository` + integration test

**Files:**
- Create: `backend/src/Activity/Infrastructure/Persistence/Doctrine/DoctrineDependencyStatsSnapshotRepository.php`
- Test: `backend/tests/Functional/Activity/Infrastructure/DoctrineDependencyStatsSnapshotRepositoryTest.php`

- [ ] **Step 1: Write the failing integration test**

```php
<?php

declare(strict_types=1);

use App\Activity\Domain\Model\DependencyStatsSnapshot;
use App\Activity\Infrastructure\Persistence\Doctrine\DoctrineDependencyStatsSnapshotRepository;
use App\Tests\Helpers\DatabaseHelper;
use Doctrine\ORM\EntityManagerInterface;

uses(DatabaseHelper::class);

beforeEach(function () {
    self::bootKernel();
    /** @var EntityManagerInterface $em */
    $em = static::getContainer()->get(EntityManagerInterface::class);
    $this->em = $em;
    $this->repo = new DoctrineDependencyStatsSnapshotRepository($em);
    $this->resetDatabase();
});

describe('DoctrineDependencyStatsSnapshotRepository', function () {
    it('saves and retrieves the latest snapshot', function () {
        $snapshot = DependencyStatsSnapshot::create(100, 80, 20, 5);
        $this->repo->save($snapshot);
        $this->em->clear();

        $latest = $this->repo->findLatest();

        expect($latest)->not->toBeNull();
        expect($latest->getTotalCount())->toBe(100);
        expect($latest->getUpToDateCount())->toBe(80);
        expect($latest->getOutdatedCount())->toBe(20);
        expect($latest->getVulnerabilityCount())->toBe(5);
    });

    it('returns null when no snapshot exists', function () {
        expect($this->repo->findLatest())->toBeNull();
        expect($this->repo->findPrevious())->toBeNull();
    });

    it('findPrevious returns the second most recent snapshot', function () {
        $first = DependencyStatsSnapshot::create(100, 80, 20, 5);
        $this->repo->save($first);
        \usleep(10000);
        $second = DependencyStatsSnapshot::create(110, 90, 20, 6);
        $this->repo->save($second);
        \usleep(10000);
        $third = DependencyStatsSnapshot::create(120, 100, 20, 7);
        $this->repo->save($third);
        $this->em->clear();

        $latest = $this->repo->findLatest();
        $previous = $this->repo->findPrevious();

        expect($latest->getTotalCount())->toBe(120);
        expect($previous->getTotalCount())->toBe(110);
    });

    it('findInRange returns snapshots within the date range', function () {
        $snapshot = DependencyStatsSnapshot::create(100, 80, 20, 5);
        $this->repo->save($snapshot);
        $this->em->clear();

        $from = new DateTimeImmutable('-1 hour');
        $to = new DateTimeImmutable('+1 hour');
        $results = $this->repo->findInRange($from, $to);

        expect($results)->toHaveCount(1);
        expect($results[0]->getTotalCount())->toBe(100);
    });

    it('findInRange excludes snapshots outside the range', function () {
        $snapshot = DependencyStatsSnapshot::create(100, 80, 20, 5);
        $this->repo->save($snapshot);
        $this->em->clear();

        $from = new DateTimeImmutable('-2 days');
        $to = new DateTimeImmutable('-1 day');
        $results = $this->repo->findInRange($from, $to);

        expect($results)->toHaveCount(0);
    });
});
```

- [ ] **Step 2: Run test, verify failure**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Functional/Activity/Infrastructure/DoctrineDependencyStatsSnapshotRepositoryTest.php
```

Expected: FAIL, class not found.

- [ ] **Step 3: Implement the repository**

```php
<?php

declare(strict_types=1);

namespace App\Activity\Infrastructure\Persistence\Doctrine;

use App\Activity\Domain\Model\DependencyStatsSnapshot;
use App\Activity\Domain\Repository\DependencyStatsSnapshotRepositoryInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineDependencyStatsSnapshotRepository implements DependencyStatsSnapshotRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(DependencyStatsSnapshot $snapshot): void
    {
        $this->entityManager->persist($snapshot);
        $this->entityManager->flush();
    }

    public function findLatest(): ?DependencyStatsSnapshot
    {
        /** @var ?DependencyStatsSnapshot */
        return $this->entityManager->getRepository(DependencyStatsSnapshot::class)
            ->createQueryBuilder('s')
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPrevious(): ?DependencyStatsSnapshot
    {
        /** @var ?DependencyStatsSnapshot */
        return $this->entityManager->getRepository(DependencyStatsSnapshot::class)
            ->createQueryBuilder('s')
            ->orderBy('s.createdAt', 'DESC')
            ->setFirstResult(1)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<DependencyStatsSnapshot> */
    public function findInRange(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        /** @var list<DependencyStatsSnapshot> */
        return $this->entityManager->getRepository(DependencyStatsSnapshot::class)
            ->createQueryBuilder('s')
            ->andWhere('s.createdAt >= :from')
            ->andWhere('s.createdAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('s.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

- [ ] **Step 4: Wire the binding**

Open `backend/config/services.yaml` and locate the section that binds repository interfaces to implementations (search for `App\Activity\Domain\Repository\BuildMetricRepositoryInterface`). Add right next to it:

```yaml
    App\Activity\Domain\Repository\DependencyStatsSnapshotRepositoryInterface:
        alias: App\Activity\Infrastructure\Persistence\Doctrine\DoctrineDependencyStatsSnapshotRepository
```

- [ ] **Step 5: Run test, verify pass**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Functional/Activity/Infrastructure/DoctrineDependencyStatsSnapshotRepositoryTest.php
```

Expected: PASS (5 tests).

- [ ] **Step 6: Lint + commit**

```
make fix-backend && make lint-backend
git add backend/src/Activity/Infrastructure/Persistence/Doctrine/DoctrineDependencyStatsSnapshotRepository.php backend/tests/Functional/Activity/Infrastructure/DoctrineDependencyStatsSnapshotRepositoryTest.php backend/config/services.yaml
git commit -m "feat(activity): doctrine repository for dependency stats snapshots"
```

---

## Task 6: `DoctrineProjectDependencyStatsSnapshotRepository` + integration test

**Files:**
- Create: `backend/src/Activity/Infrastructure/Persistence/Doctrine/DoctrineProjectDependencyStatsSnapshotRepository.php`
- Test: `backend/tests/Functional/Activity/Infrastructure/DoctrineProjectDependencyStatsSnapshotRepositoryTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Activity\Domain\Model\ProjectDependencyStatsSnapshot;
use App\Activity\Infrastructure\Persistence\Doctrine\DoctrineProjectDependencyStatsSnapshotRepository;
use App\Tests\Helpers\DatabaseHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    self::bootKernel();
    /** @var EntityManagerInterface $em */
    $em = static::getContainer()->get(EntityManagerInterface::class);
    $this->em = $em;
    $this->repo = new DoctrineProjectDependencyStatsSnapshotRepository($em);
    $this->resetDatabase();
});

describe('DoctrineProjectDependencyStatsSnapshotRepository', function () {
    it('saves and finds latest for a project', function () {
        $projectId = Uuid::v7();
        $snapshot = ProjectDependencyStatsSnapshot::create($projectId, 50, 40, 10, 1);
        $this->repo->save($snapshot);
        $this->em->clear();

        $latest = $this->repo->findLatestForProject($projectId);

        expect($latest)->not->toBeNull();
        expect($latest->getProjectId()->equals($projectId))->toBeTrue();
        expect($latest->getTotalCount())->toBe(50);
    });

    it('findLatestForProject returns null for unknown project', function () {
        expect($this->repo->findLatestForProject(Uuid::v7()))->toBeNull();
    });

    it('findPreviousForProject returns the second most recent for a project', function () {
        $projectId = Uuid::v7();
        $this->repo->save(ProjectDependencyStatsSnapshot::create($projectId, 50, 40, 10, 1));
        \usleep(10000);
        $this->repo->save(ProjectDependencyStatsSnapshot::create($projectId, 60, 50, 10, 1));
        \usleep(10000);
        $this->repo->save(ProjectDependencyStatsSnapshot::create($projectId, 70, 60, 10, 1));
        $this->em->clear();

        $latest = $this->repo->findLatestForProject($projectId);
        $previous = $this->repo->findPreviousForProject($projectId);

        expect($latest->getTotalCount())->toBe(70);
        expect($previous->getTotalCount())->toBe(60);
    });

    it('findLatestForProjects returns batch indexed by projectId', function () {
        $a = Uuid::v7();
        $b = Uuid::v7();
        $this->repo->save(ProjectDependencyStatsSnapshot::create($a, 10, 8, 2, 0));
        \usleep(10000);
        $this->repo->save(ProjectDependencyStatsSnapshot::create($a, 20, 18, 2, 0));
        $this->repo->save(ProjectDependencyStatsSnapshot::create($b, 100, 50, 50, 5));
        $this->em->clear();

        $latests = $this->repo->findLatestForProjects([$a, $b]);

        expect($latests)->toHaveKey($a->toRfc4122());
        expect($latests)->toHaveKey($b->toRfc4122());
        expect($latests[$a->toRfc4122()]->getTotalCount())->toBe(20);
        expect($latests[$b->toRfc4122()]->getTotalCount())->toBe(100);
    });

    it('findPreviousForProjects returns the second-latest per project', function () {
        $a = Uuid::v7();
        $this->repo->save(ProjectDependencyStatsSnapshot::create($a, 10, 5, 5, 0));
        \usleep(10000);
        $this->repo->save(ProjectDependencyStatsSnapshot::create($a, 20, 15, 5, 0));
        \usleep(10000);
        $this->repo->save(ProjectDependencyStatsSnapshot::create($a, 30, 25, 5, 0));
        $this->em->clear();

        $previous = $this->repo->findPreviousForProjects([$a]);

        expect($previous)->toHaveKey($a->toRfc4122());
        expect($previous[$a->toRfc4122()]->getTotalCount())->toBe(20);
    });

    it('findLatestForProjects with empty list returns empty array', function () {
        expect($this->repo->findLatestForProjects([]))->toBe([]);
        expect($this->repo->findPreviousForProjects([]))->toBe([]);
    });
});
```

- [ ] **Step 2: Run test, verify failure**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Functional/Activity/Infrastructure/DoctrineProjectDependencyStatsSnapshotRepositoryTest.php
```

Expected: FAIL, class not found.

- [ ] **Step 3: Implement the repository**

```php
<?php

declare(strict_types=1);

namespace App\Activity\Infrastructure\Persistence\Doctrine;

use App\Activity\Domain\Model\ProjectDependencyStatsSnapshot;
use App\Activity\Domain\Repository\ProjectDependencyStatsSnapshotRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineProjectDependencyStatsSnapshotRepository implements ProjectDependencyStatsSnapshotRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(ProjectDependencyStatsSnapshot $snapshot): void
    {
        $this->entityManager->persist($snapshot);
        $this->entityManager->flush();
    }

    public function findLatestForProject(Uuid $projectId): ?ProjectDependencyStatsSnapshot
    {
        /** @var ?ProjectDependencyStatsSnapshot */
        return $this->entityManager->getRepository(ProjectDependencyStatsSnapshot::class)
            ->createQueryBuilder('s')
            ->andWhere('s.projectId = :pid')
            ->setParameter('pid', $projectId)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPreviousForProject(Uuid $projectId): ?ProjectDependencyStatsSnapshot
    {
        /** @var ?ProjectDependencyStatsSnapshot */
        return $this->entityManager->getRepository(ProjectDependencyStatsSnapshot::class)
            ->createQueryBuilder('s')
            ->andWhere('s.projectId = :pid')
            ->setParameter('pid', $projectId)
            ->orderBy('s.createdAt', 'DESC')
            ->setFirstResult(1)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param list<Uuid> $projectIds
     * @return array<string, ProjectDependencyStatsSnapshot>
     */
    public function findLatestForProjects(array $projectIds): array
    {
        return $this->findNthForProjects($projectIds, 0);
    }

    /**
     * @param list<Uuid> $projectIds
     * @return array<string, ProjectDependencyStatsSnapshot>
     */
    public function findPreviousForProjects(array $projectIds): array
    {
        return $this->findNthForProjects($projectIds, 1);
    }

    /**
     * @param list<Uuid> $projectIds
     * @return array<string, ProjectDependencyStatsSnapshot>
     */
    private function findNthForProjects(array $projectIds, int $offset): array
    {
        if ($projectIds === []) {
            return [];
        }

        $result = [];
        foreach ($projectIds as $projectId) {
            /** @var ?ProjectDependencyStatsSnapshot $snapshot */
            $snapshot = $this->entityManager->getRepository(ProjectDependencyStatsSnapshot::class)
                ->createQueryBuilder('s')
                ->andWhere('s.projectId = :pid')
                ->setParameter('pid', $projectId)
                ->orderBy('s.createdAt', 'DESC')
                ->setFirstResult($offset)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($snapshot !== null) {
                $result[$projectId->toRfc4122()] = $snapshot;
            }
        }

        return $result;
    }
}
```

Note: a single SQL with `ROW_NUMBER() OVER (PARTITION BY project_id ORDER BY created_at DESC)` would be more efficient, but for 5 projects the N+1 is negligible (~10 fast index lookups). Keep it readable.

- [ ] **Step 4: Wire the binding**

In `backend/config/services.yaml`, next to the previous binding:

```yaml
    App\Activity\Domain\Repository\ProjectDependencyStatsSnapshotRepositoryInterface:
        alias: App\Activity\Infrastructure\Persistence\Doctrine\DoctrineProjectDependencyStatsSnapshotRepository
```

- [ ] **Step 5: Run test, verify pass**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Functional/Activity/Infrastructure/DoctrineProjectDependencyStatsSnapshotRepositoryTest.php
```

Expected: PASS (6 tests).

- [ ] **Step 6: Lint + commit**

```
make fix-backend && make lint-backend
git add backend/src/Activity/Infrastructure/Persistence/Doctrine/DoctrineProjectDependencyStatsSnapshotRepository.php backend/tests/Functional/Activity/Infrastructure/DoctrineProjectDependencyStatsSnapshotRepositoryTest.php backend/config/services.yaml
git commit -m "feat(activity): doctrine repository for project dependency stats snapshots"
```

---

## Task 7: Wire `Project::markSynced()` and add `findRecentlySynced` to ProjectRepository

`Project::markSynced()` exists but is never called, so `last_synced_at` is always null in the DB. We need to call it from `ScanProjectHandler` after a successful scan, and add a method to the repository to fetch the N most recently synced projects.

**Files:**
- Modify: `backend/src/Catalog/Application/CommandHandler/ScanProjectHandler.php`
- Modify: `backend/src/Catalog/Domain/Repository/ProjectRepositoryInterface.php`
- Modify: `backend/src/Catalog/Infrastructure/Persistence/Doctrine/DoctrineProjectRepository.php`
- Test: `backend/tests/Functional/Catalog/Infrastructure/DoctrineProjectRepositoryRecentlySyncedTest.php`

- [ ] **Step 1: Add `findRecentlySynced` to the interface**

In `backend/src/Catalog/Domain/Repository/ProjectRepositoryInterface.php`, add right after `findAllWithProvider`:

```php
    /** @return list<Project> */
    public function findRecentlySynced(int $limit = 5): array;
```

- [ ] **Step 2: Write the failing repository test**

Create `backend/tests/Functional/Catalog/Infrastructure/DoctrineProjectRepositoryRecentlySyncedTest.php`:

```php
<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    self::bootKernel();
    $this->repo = static::getContainer()->get(ProjectRepositoryInterface::class);
    $this->resetDatabase();
});

describe('DoctrineProjectRepository::findRecentlySynced', function () {
    it('returns projects ordered by lastSyncedAt desc, limited', function () {
        $a = Project::create(name: 'A', slug: 'a', description: null, repositoryUrl: 'https://x/a', defaultBranch: 'main', visibility: ProjectVisibility::Private, ownerId: Uuid::v7());
        $b = Project::create(name: 'B', slug: 'b', description: null, repositoryUrl: 'https://x/b', defaultBranch: 'main', visibility: ProjectVisibility::Private, ownerId: Uuid::v7());
        $c = Project::create(name: 'C', slug: 'c', description: null, repositoryUrl: 'https://x/c', defaultBranch: 'main', visibility: ProjectVisibility::Private, ownerId: Uuid::v7());
        $a->markSynced();
        $this->repo->save($a);
        \usleep(10000);
        $b->markSynced();
        $this->repo->save($b);
        \usleep(10000);
        $c->markSynced();
        $this->repo->save($c);

        $results = $this->repo->findRecentlySynced(2);

        expect($results)->toHaveCount(2);
        expect($results[0]->getSlug())->toBe('c');
        expect($results[1]->getSlug())->toBe('b');
    });

    it('excludes projects that were never synced', function () {
        $a = Project::create(name: 'A', slug: 'a', description: null, repositoryUrl: 'https://x/a', defaultBranch: 'main', visibility: ProjectVisibility::Private, ownerId: Uuid::v7());
        $b = Project::create(name: 'B', slug: 'b', description: null, repositoryUrl: 'https://x/b', defaultBranch: 'main', visibility: ProjectVisibility::Private, ownerId: Uuid::v7());
        $a->markSynced();
        $this->repo->save($a);
        $this->repo->save($b);

        $results = $this->repo->findRecentlySynced(5);

        expect($results)->toHaveCount(1);
        expect($results[0]->getSlug())->toBe('a');
    });
});
```

- [ ] **Step 3: Run test, verify failure**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Functional/Catalog/Infrastructure/DoctrineProjectRepositoryRecentlySyncedTest.php
```

Expected: FAIL, method `findRecentlySynced` not defined.

- [ ] **Step 4: Implement `findRecentlySynced` in `DoctrineProjectRepository`**

In `backend/src/Catalog/Infrastructure/Persistence/Doctrine/DoctrineProjectRepository.php`, add this method (after `findAllWithProvider`):

```php
    /** @return list<Project> */
    public function findRecentlySynced(int $limit = 5): array
    {
        /** @var list<Project> */
        return $this->entityManager->getRepository(Project::class)
            ->createQueryBuilder('p')
            ->andWhere('p.lastSyncedAt IS NOT NULL')
            ->orderBy('p.lastSyncedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
```

- [ ] **Step 5: Run test, verify pass**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Functional/Catalog/Infrastructure/DoctrineProjectRepositoryRecentlySyncedTest.php
```

Expected: PASS (2 tests).

- [ ] **Step 6: Update stubs in existing tests**

Per CLAUDE.md, when an interface gains a method, all anonymous-class stubs implementing it must be updated. Find them:

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend grep -rln "implements ProjectRepositoryInterface" tests/
```

For each match, open the file and add the method to the anonymous class:

```php
public function findRecentlySynced(int $limit = 5): array
{
    return [];
}
```

- [ ] **Step 7: Verify all tests still pass**

```
make test-backend
```

Expected: 0 failures, 0 errors. (Watch for any test that previously implemented `ProjectRepositoryInterface` and now fails due to missing method.)

- [ ] **Step 8: Wire `Project::markSynced()` in `ScanProjectHandler`**

In `backend/src/Catalog/Application/CommandHandler/ScanProjectHandler.php`, locate the end of `__invoke` (just before `$this->dependencyWriter->removeStaleByProjectId(...)` on line 123). Add right before the existing `$this->eventBus->dispatch(new ProjectScannedEvent(...))` on line 125:

```php
        $project->markSynced();
        $this->projectRepository->save($project);
```

Also do the same in the early-return branch on line 60 (when `$scanResult->stacks === [] && $scanResult->dependencies === []`), right before the dispatch:

```php
        if ($scanResult->stacks === [] && $scanResult->dependencies === []) {
            $project->markSynced();
            $this->projectRepository->save($project);

            $this->eventBus->dispatch(new ProjectScannedEvent(
                projectId: $command->projectId,
                scanResult: $scanResult,
            ));

            return new ScanResultOutput(
                stacksDetected: 0,
                dependenciesDetected: 0,
                stacks: [],
                dependencies: [],
            );
        }
```

- [ ] **Step 9: Verify ScanProjectHandler tests still pass**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Catalog/Application/CommandHandler/ScanProjectHandlerTest.php
```

Expected: PASS. If any test fails because the project save count changed, update the assertion (we now save twice instead of once on the success branch).

- [ ] **Step 10: Lint + commit**

```
make fix-backend && make lint-backend
git add backend/src/Catalog/Application/CommandHandler/ScanProjectHandler.php backend/src/Catalog/Domain/Repository/ProjectRepositoryInterface.php backend/src/Catalog/Infrastructure/Persistence/Doctrine/DoctrineProjectRepository.php backend/tests/Functional/Catalog/Infrastructure/DoctrineProjectRepositoryRecentlySyncedTest.php backend/tests/
git commit -m "feat(catalog): wire Project::markSynced and expose findRecentlySynced"
```

---

## Task 8: `GlobalSyncJobCompletedEvent` + dispatch from completion sites

**Files:**
- Create: `backend/src/Sync/Domain/Event/GlobalSyncJobCompletedEvent.php`
- Modify: `backend/src/Sync/Application/CommandHandler/GlobalSyncHandler.php`
- Modify: `backend/src/Sync/Application/EventListener/GlobalSyncVersionProgressListener.php`

- [ ] **Step 1: Create the event**

```php
<?php

declare(strict_types=1);

namespace App\Sync\Domain\Event;

final readonly class GlobalSyncJobCompletedEvent
{
    public function __construct(
        public string $jobId,
    ) {
    }
}
```

- [ ] **Step 2: Dispatch from `GlobalSyncHandler`**

Open `backend/src/Sync/Application/CommandHandler/GlobalSyncHandler.php`. Add the import:

```php
use App\Sync\Domain\Event\GlobalSyncJobCompletedEvent;
```

Find the empty-projects branch (line 56-63). After `$this->publishProgress($command->syncId, $job);` and before `return;`, add:

```php
                $this->eventBus->dispatch(new GlobalSyncJobCompletedEvent($job->getId()->toRfc4122()));
```

If the handler does not already inject `MessageBusInterface $eventBus`, add it to the constructor (look for `private MessageBusInterface $commandBus,` and add a sibling param `private MessageBusInterface $eventBus,`). If a constructor change is needed, update any test that instantiates `GlobalSyncHandler` directly to pass an anonymous-class `MessageBusInterface` stub.

- [ ] **Step 3: Dispatch from `GlobalSyncVersionProgressListener`**

Open `backend/src/Sync/Application/EventListener/GlobalSyncVersionProgressListener.php`. Add the import:

```php
use App\Sync\Domain\Event\GlobalSyncJobCompletedEvent;
```

Add `MessageBusInterface $eventBus` to the constructor:

```php
    public function __construct(
        private GlobalSyncJobRepositoryInterface $repository,
        private HubInterface $mercureHub,
        private LoggerInterface $logger,
        private \Symfony\Component\Messenger\MessageBusInterface $eventBus,
    ) {
    }
```

In `transitionToScanCve` (line 61), after `$this->publishProgress($job, null);`, add:

```php
        $this->eventBus->dispatch(new GlobalSyncJobCompletedEvent($job->getId()->toRfc4122()));
```

- [ ] **Step 4: Update stubs in tests that instantiate this listener**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend grep -rln "new GlobalSyncVersionProgressListener" tests/
```

For each match, add an anonymous `MessageBusInterface` stub argument:

```php
new class () implements \Symfony\Component\Messenger\MessageBusInterface {
    public function dispatch(object $message, array $stamps = []): \Symfony\Component\Messenger\Envelope
    {
        return \Symfony\Component\Messenger\Envelope::wrap($message, $stamps);
    }
},
```

- [ ] **Step 5: Verify tests pass**

```
make test-backend
```

Expected: 0 failures.

- [ ] **Step 6: Lint + commit**

```
make fix-backend && make lint-backend
git add backend/src/Sync/Domain/Event/GlobalSyncJobCompletedEvent.php backend/src/Sync/Application/CommandHandler/GlobalSyncHandler.php backend/src/Sync/Application/EventListener/GlobalSyncVersionProgressListener.php backend/tests/
git commit -m "feat(sync): introduce GlobalSyncJobCompletedEvent dispatched on completion"
```

---

## Task 9: `CaptureDependencyStatsSnapshotListener`

**Files:**
- Create: `backend/src/Activity/Application/EventListener/CaptureDependencyStatsSnapshotListener.php`
- Test: `backend/tests/Unit/Activity/Application/EventListener/CaptureDependencyStatsSnapshotListenerTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Activity\Application\EventListener\CaptureDependencyStatsSnapshotListener;
use App\Activity\Domain\Model\DependencyStatsSnapshot;
use App\Activity\Domain\Repository\DependencyStatsSnapshotRepositoryInterface;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Sync\Domain\Event\GlobalSyncJobCompletedEvent;
use Psr\Log\NullLogger;

function stubSnapshotRepoCollector(): DependencyStatsSnapshotRepositoryInterface
{
    return new class () implements DependencyStatsSnapshotRepositoryInterface {
        /** @var list<DependencyStatsSnapshot> */
        public array $saved = [];
        public function save(DependencyStatsSnapshot $snapshot): void
        {
            $this->saved[] = $snapshot;
        }
        public function findLatest(): ?DependencyStatsSnapshot { return null; }
        public function findPrevious(): ?DependencyStatsSnapshot { return null; }
        public function findInRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array { return []; }
    };
}

function stubDependencyRepoStats(int $total, int $outdated, int $vulns): DependencyRepositoryInterface
{
    return new class ($total, $outdated, $vulns) implements DependencyRepositoryInterface {
        public function __construct(private int $t, private int $o, private int $v) {}
        public function getStats(array $filters = []): array
        {
            return ['total' => $this->t, 'outdated' => $this->o, 'totalVulnerabilities' => $this->v];
        }
        // unused methods — required by interface
        public function findById(\Symfony\Component\Uid\Uuid $id): ?\App\Dependency\Domain\Model\Dependency { return null; }
        public function findByName(string $name, string $packageManager): ?\App\Dependency\Domain\Model\Dependency { return null; }
        public function findByProjectId(\Symfony\Component\Uid\Uuid $projectId): array { return []; }
        public function save(\App\Dependency\Domain\Model\Dependency $dependency): void {}
        public function delete(\App\Dependency\Domain\Model\Dependency $dependency): void {}
        public function findFiltered(array $filters, int $page, int $perPage, string $sort, string $sortDir): array { return []; }
        public function countFiltered(array $filters): int { return 0; }
        public function findUniquePackages(): array { return []; }
        public function getStatsSingle(array $filters = []): array { return ['total'=>0,'outdated'=>0,'totalVulnerabilities'=>0]; }
    };
}

describe('CaptureDependencyStatsSnapshotListener', function () {
    it('captures a snapshot from current dependency stats on event', function () {
        $snapshotRepo = stubSnapshotRepoCollector();
        $depRepo = stubDependencyRepoStats(total: 100, outdated: 30, vulns: 5);
        $listener = new CaptureDependencyStatsSnapshotListener($snapshotRepo, $depRepo, new NullLogger());

        $listener(new GlobalSyncJobCompletedEvent('job-1'));

        expect($snapshotRepo->saved)->toHaveCount(1);
        $saved = $snapshotRepo->saved[0];
        expect($saved->getTotalCount())->toBe(100);
        expect($saved->getUpToDateCount())->toBe(70);
        expect($saved->getOutdatedCount())->toBe(30);
        expect($saved->getVulnerabilityCount())->toBe(5);
    });

    it('does not throw when the repo throws', function () {
        $throwingRepo = new class () implements DependencyStatsSnapshotRepositoryInterface {
            public function save(DependencyStatsSnapshot $snapshot): void { throw new \RuntimeException('boom'); }
            public function findLatest(): ?DependencyStatsSnapshot { return null; }
            public function findPrevious(): ?DependencyStatsSnapshot { return null; }
            public function findInRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array { return []; }
        };
        $listener = new CaptureDependencyStatsSnapshotListener(
            $throwingRepo,
            stubDependencyRepoStats(10, 5, 0),
            new NullLogger(),
        );

        $listener(new GlobalSyncJobCompletedEvent('job-1'));

        expect(true)->toBeTrue(); // reached this line means no exception bubbled up
    });
});
```

If the `DependencyRepositoryInterface` signature differs from the stub above (extra methods), adjust the anonymous class — read the interface first:

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend cat src/Dependency/Domain/Repository/DependencyRepositoryInterface.php
```

Add any missing methods returning trivial defaults.

- [ ] **Step 2: Run test, verify failure**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Activity/Application/EventListener/CaptureDependencyStatsSnapshotListenerTest.php
```

Expected: FAIL, listener class not found.

- [ ] **Step 3: Implement the listener**

```php
<?php

declare(strict_types=1);

namespace App\Activity\Application\EventListener;

use App\Activity\Domain\Model\DependencyStatsSnapshot;
use App\Activity\Domain\Repository\DependencyStatsSnapshotRepositoryInterface;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Sync\Domain\Event\GlobalSyncJobCompletedEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

final readonly class CaptureDependencyStatsSnapshotListener
{
    public function __construct(
        private DependencyStatsSnapshotRepositoryInterface $snapshotRepository,
        private DependencyRepositoryInterface $dependencyRepository,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function __invoke(GlobalSyncJobCompletedEvent $event): void
    {
        try {
            $stats = $this->dependencyRepository->getStats([]);
            $total = (int) ($stats['total'] ?? 0);
            $outdated = (int) ($stats['outdated'] ?? 0);
            $vulnerabilities = (int) ($stats['totalVulnerabilities'] ?? 0);

            $snapshot = DependencyStatsSnapshot::create(
                totalCount: $total,
                upToDateCount: \max(0, $total - $outdated),
                outdatedCount: $outdated,
                vulnerabilityCount: $vulnerabilities,
            );

            $this->snapshotRepository->save($snapshot);
        } catch (Throwable $e) {
            $this->logger->error('Failed to capture dependency stats snapshot', [
                'jobId' => $event->jobId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

- [ ] **Step 4: Run test, verify pass**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Activity/Application/EventListener/CaptureDependencyStatsSnapshotListenerTest.php
```

Expected: PASS (2 tests).

- [ ] **Step 5: Lint + commit**

```
make fix-backend && make lint-backend
git add backend/src/Activity/Application/EventListener/CaptureDependencyStatsSnapshotListener.php backend/tests/Unit/Activity/Application/EventListener/CaptureDependencyStatsSnapshotListenerTest.php
git commit -m "feat(activity): capture global dependency stats snapshot on sync completion"
```

---

## Task 10: `CaptureProjectDependencyStatsSnapshotListener`

The per-project snapshot is captured on `ProjectScannedEvent` (already dispatched by `ScanProjectHandler`). At this point, the project's dependencies have been upserted in the DB.

**Files:**
- Create: `backend/src/Activity/Application/EventListener/CaptureProjectDependencyStatsSnapshotListener.php`
- Test: `backend/tests/Unit/Activity/Application/EventListener/CaptureProjectDependencyStatsSnapshotListenerTest.php`

- [ ] **Step 1: Read ProjectScannedEvent to confirm field name**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend cat src/Catalog/Domain/Event/ProjectScannedEvent.php
```

Confirm the public field is `projectId` (a string). If it has a different name, adjust the test and listener accordingly.

- [ ] **Step 2: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Activity\Application\EventListener\CaptureProjectDependencyStatsSnapshotListener;
use App\Activity\Domain\Model\ProjectDependencyStatsSnapshot;
use App\Activity\Domain\Repository\ProjectDependencyStatsSnapshotRepositoryInterface;
use App\Catalog\Domain\Event\ProjectScannedEvent;
use App\Catalog\Domain\Model\Project;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Uid\Uuid;

function stubProjectSnapshotRepoCollector(): ProjectDependencyStatsSnapshotRepositoryInterface
{
    return new class () implements ProjectDependencyStatsSnapshotRepositoryInterface {
        /** @var list<ProjectDependencyStatsSnapshot> */
        public array $saved = [];
        public function save(ProjectDependencyStatsSnapshot $snapshot): void { $this->saved[] = $snapshot; }
        public function findLatestForProject(Uuid $projectId): ?ProjectDependencyStatsSnapshot { return null; }
        public function findPreviousForProject(Uuid $projectId): ?ProjectDependencyStatsSnapshot { return null; }
        public function findLatestForProjects(array $projectIds): array { return []; }
        public function findPreviousForProjects(array $projectIds): array { return []; }
    };
}

function stubDependencyRepoFilteredStats(int $total, int $outdated, int $vulns): DependencyRepositoryInterface
{
    // Reuse the helper from the previous test if visible, otherwise inline a fresh stub.
    // (Pest functions must be uniquely named per test file.)
    return new class ($total, $outdated, $vulns) implements DependencyRepositoryInterface {
        public function __construct(private int $t, private int $o, private int $v) {}
        public function getStats(array $filters = []): array
        {
            return ['total' => $this->t, 'outdated' => $this->o, 'totalVulnerabilities' => $this->v];
        }
        public function findById(Uuid $id): ?\App\Dependency\Domain\Model\Dependency { return null; }
        public function findByName(string $name, string $packageManager): ?\App\Dependency\Domain\Model\Dependency { return null; }
        public function findByProjectId(Uuid $projectId): array { return []; }
        public function save(\App\Dependency\Domain\Model\Dependency $dependency): void {}
        public function delete(\App\Dependency\Domain\Model\Dependency $dependency): void {}
        public function findFiltered(array $filters, int $page, int $perPage, string $sort, string $sortDir): array { return []; }
        public function countFiltered(array $filters): int { return 0; }
        public function findUniquePackages(): array { return []; }
        public function getStatsSingle(array $filters = []): array { return ['total'=>0,'outdated'=>0,'totalVulnerabilities'=>0]; }
    };
}

describe('CaptureProjectDependencyStatsSnapshotListener', function () {
    it('captures a snapshot for the scanned project', function () {
        $projectId = Uuid::v7();
        $snapshotRepo = stubProjectSnapshotRepoCollector();
        $depRepo = stubDependencyRepoFilteredStats(total: 50, outdated: 10, vulns: 1);
        $listener = new CaptureProjectDependencyStatsSnapshotListener($snapshotRepo, $depRepo, new NullLogger());

        $listener(new ProjectScannedEvent(
            projectId: $projectId->toRfc4122(),
            scanResult: new \App\Catalog\Application\Service\ScanResult(stacks: [], dependencies: []),
        ));

        expect($snapshotRepo->saved)->toHaveCount(1);
        expect($snapshotRepo->saved[0]->getProjectId()->equals($projectId))->toBeTrue();
        expect($snapshotRepo->saved[0]->getTotalCount())->toBe(50);
        expect($snapshotRepo->saved[0]->getUpToDateCount())->toBe(40);
        expect($snapshotRepo->saved[0]->getOutdatedCount())->toBe(10);
        expect($snapshotRepo->saved[0]->getVulnerabilityCount())->toBe(1);
    });

    it('does not throw when the repo throws', function () {
        $throwingRepo = new class () implements ProjectDependencyStatsSnapshotRepositoryInterface {
            public function save(ProjectDependencyStatsSnapshot $snapshot): void { throw new \RuntimeException('boom'); }
            public function findLatestForProject(Uuid $projectId): ?ProjectDependencyStatsSnapshot { return null; }
            public function findPreviousForProject(Uuid $projectId): ?ProjectDependencyStatsSnapshot { return null; }
            public function findLatestForProjects(array $projectIds): array { return []; }
            public function findPreviousForProjects(array $projectIds): array { return []; }
        };
        $listener = new CaptureProjectDependencyStatsSnapshotListener(
            $throwingRepo,
            stubDependencyRepoFilteredStats(10, 5, 0),
            new NullLogger(),
        );

        $listener(new ProjectScannedEvent(
            projectId: Uuid::v7()->toRfc4122(),
            scanResult: new \App\Catalog\Application\Service\ScanResult(stacks: [], dependencies: []),
        ));

        expect(true)->toBeTrue();
    });
});
```

If `ScanResult` lives at a different namespace, find it:

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend grep -rln "class ScanResult" src/Catalog
```

Use the actual namespace in the test.

- [ ] **Step 3: Run test, verify failure**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Activity/Application/EventListener/CaptureProjectDependencyStatsSnapshotListenerTest.php
```

Expected: FAIL, listener class not found.

- [ ] **Step 4: Implement the listener**

```php
<?php

declare(strict_types=1);

namespace App\Activity\Application\EventListener;

use App\Activity\Domain\Model\ProjectDependencyStatsSnapshot;
use App\Activity\Domain\Repository\ProjectDependencyStatsSnapshotRepositoryInterface;
use App\Catalog\Domain\Event\ProjectScannedEvent;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Throwable;

final readonly class CaptureProjectDependencyStatsSnapshotListener
{
    public function __construct(
        private ProjectDependencyStatsSnapshotRepositoryInterface $snapshotRepository,
        private DependencyRepositoryInterface $dependencyRepository,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function __invoke(ProjectScannedEvent $event): void
    {
        try {
            $projectId = Uuid::fromString($event->projectId);
            $stats = $this->dependencyRepository->getStats(['projectId' => $event->projectId]);
            $total = (int) ($stats['total'] ?? 0);
            $outdated = (int) ($stats['outdated'] ?? 0);
            $vulnerabilities = (int) ($stats['totalVulnerabilities'] ?? 0);

            $snapshot = ProjectDependencyStatsSnapshot::create(
                projectId: $projectId,
                totalCount: $total,
                upToDateCount: \max(0, $total - $outdated),
                outdatedCount: $outdated,
                vulnerabilityCount: $vulnerabilities,
            );

            $this->snapshotRepository->save($snapshot);
        } catch (Throwable $e) {
            $this->logger->error('Failed to capture project dependency stats snapshot', [
                'projectId' => $event->projectId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

- [ ] **Step 5: Run test, verify pass**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Activity/Application/EventListener/CaptureProjectDependencyStatsSnapshotListenerTest.php
```

Expected: PASS (2 tests).

- [ ] **Step 6: Lint + commit**

```
make fix-backend && make lint-backend
git add backend/src/Activity/Application/EventListener/CaptureProjectDependencyStatsSnapshotListener.php backend/tests/Unit/Activity/Application/EventListener/CaptureProjectDependencyStatsSnapshotListenerTest.php
git commit -m "feat(activity): capture per-project dependency stats snapshot on scan"
```

---

## Task 11: Sub-DTOs and extend `DashboardOutput`

**Files:**
- Create: `backend/src/Activity/Application/DTO/DashboardSnapshotPoint.php`
- Create: `backend/src/Activity/Application/DTO/DashboardRecentProject.php`
- Modify: `backend/src/Activity/Application/DTO/DashboardOutput.php`

- [ ] **Step 1: Create `DashboardSnapshotPoint`**

```php
<?php

declare(strict_types=1);

namespace App\Activity\Application\DTO;

final readonly class DashboardSnapshotPoint
{
    public function __construct(
        public string $timestamp,
        public int $totalCount,
        public int $upToDateCount,
        public int $outdatedCount,
        public float $upToDateRatio,
    ) {
    }
}
```

- [ ] **Step 2: Create `DashboardRecentProject`**

```php
<?php

declare(strict_types=1);

namespace App\Activity\Application\DTO;

final readonly class DashboardRecentProject
{
    public function __construct(
        public string $projectId,
        public string $name,
        public string $slug,
        public ?string $lastSyncedAt,
        public int $totalDependencies,
        public int $upToDateCount,
        public int $outdatedCount,
        public float $upToDateRatio,
        public int $deltaSinceLastSync,
    ) {
    }
}
```

- [ ] **Step 3: Extend `DashboardOutput`**

Replace the existing file with:

```php
<?php

declare(strict_types=1);

namespace App\Activity\Application\DTO;

final readonly class DashboardOutput
{
    /**
     * @param list<DashboardMetric>          $metrics
     * @param list<DashboardSnapshotPoint>   $history
     * @param list<DashboardRecentProject>   $recentProjects
     */
    public function __construct(
        public array $metrics,
        public array $history = [],
        public array $recentProjects = [],
    ) {
    }
}
```

The defaults preserve backward compatibility — the existing test `GetDashboardHandlerTest` that asserts `metrics empty` still passes for now.

- [ ] **Step 4: Lint and verify all tests still pass**

```
make fix-backend && make lint-backend && make test-backend
```

Expected: 0 failures.

- [ ] **Step 5: Commit**

```
git add backend/src/Activity/Application/DTO/DashboardSnapshotPoint.php backend/src/Activity/Application/DTO/DashboardRecentProject.php backend/src/Activity/Application/DTO/DashboardOutput.php
git commit -m "feat(activity): extend DashboardOutput with history and recent projects DTOs"
```

---

## Task 12: Extend `GetDashboardQuery` with range parameter

**Files:**
- Modify: `backend/src/Activity/Application/Query/GetDashboardQuery.php`

- [ ] **Step 1: Replace the file**

```php
<?php

declare(strict_types=1);

namespace App\Activity\Application\Query;

final readonly class GetDashboardQuery
{
    public const VALID_RANGES = ['7d', '30d', '90d', '1y'];

    public function __construct(
        public string $userId,
        public string $range = '30d',
    ) {
    }
}
```

- [ ] **Step 2: Verify existing test still passes**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Activity/Application/QueryHandler/GetDashboardHandlerTest.php
```

Expected: PASS.

- [ ] **Step 3: Lint + commit**

```
make fix-backend && make lint-backend
git add backend/src/Activity/Application/Query/GetDashboardQuery.php
git commit -m "feat(activity): add range parameter to GetDashboardQuery"
```

---

## Task 13: Rewrite `GetDashboardHandler` (KPI cards + history + recent projects)

This is the biggest backend task. We rewrite the handler in one go because the three concerns share input data and constructors. Tests are written upfront and the handler is implemented to satisfy all of them.

**Files:**
- Modify: `backend/src/Activity/Application/QueryHandler/GetDashboardHandler.php`
- Modify: `backend/tests/Unit/Activity/Application/QueryHandler/GetDashboardHandlerTest.php`

- [ ] **Step 1: Replace the existing test file**

Overwrite `backend/tests/Unit/Activity/Application/QueryHandler/GetDashboardHandlerTest.php` with:

```php
<?php

declare(strict_types=1);

use App\Activity\Application\DTO\DashboardOutput;
use App\Activity\Application\DTO\DashboardRecentProject;
use App\Activity\Application\DTO\DashboardSnapshotPoint;
use App\Activity\Application\Query\GetDashboardQuery;
use App\Activity\Application\QueryHandler\GetDashboardHandler;
use App\Activity\Domain\Model\DependencyStatsSnapshot;
use App\Activity\Domain\Model\ProjectDependencyStatsSnapshot;
use App\Activity\Domain\Repository\DependencyStatsSnapshotRepositoryInterface;
use App\Activity\Domain\Repository\ProjectDependencyStatsSnapshotRepositoryInterface;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubGlobalSnapshotRepo(?DependencyStatsSnapshot $latest, ?DependencyStatsSnapshot $previous, array $rangeResults = []): DependencyStatsSnapshotRepositoryInterface
{
    return new class ($latest, $previous, $rangeResults) implements DependencyStatsSnapshotRepositoryInterface {
        public function __construct(private ?DependencyStatsSnapshot $l, private ?DependencyStatsSnapshot $p, private array $range) {}
        public function save(DependencyStatsSnapshot $snapshot): void {}
        public function findLatest(): ?DependencyStatsSnapshot { return $this->l; }
        public function findPrevious(): ?DependencyStatsSnapshot { return $this->p; }
        public function findInRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array { return $this->range; }
    };
}

function stubProjectSnapshotRepo(array $latest, array $previous): ProjectDependencyStatsSnapshotRepositoryInterface
{
    return new class ($latest, $previous) implements ProjectDependencyStatsSnapshotRepositoryInterface {
        public function __construct(private array $latest, private array $previous) {}
        public function save(ProjectDependencyStatsSnapshot $snapshot): void {}
        public function findLatestForProject(Uuid $projectId): ?ProjectDependencyStatsSnapshot { return $this->latest[$projectId->toRfc4122()] ?? null; }
        public function findPreviousForProject(Uuid $projectId): ?ProjectDependencyStatsSnapshot { return $this->previous[$projectId->toRfc4122()] ?? null; }
        public function findLatestForProjects(array $projectIds): array
        {
            $r = [];
            foreach ($projectIds as $id) {
                if (isset($this->latest[$id->toRfc4122()])) $r[$id->toRfc4122()] = $this->latest[$id->toRfc4122()];
            }
            return $r;
        }
        public function findPreviousForProjects(array $projectIds): array
        {
            $r = [];
            foreach ($projectIds as $id) {
                if (isset($this->previous[$id->toRfc4122()])) $r[$id->toRfc4122()] = $this->previous[$id->toRfc4122()];
            }
            return $r;
        }
    };
}

function stubDepRepoStatsHandler(int $total, int $outdated, int $vulns): DependencyRepositoryInterface
{
    return new class ($total, $outdated, $vulns) implements DependencyRepositoryInterface {
        public function __construct(private int $t, private int $o, private int $v) {}
        public function getStats(array $filters = []): array { return ['total'=>$this->t,'outdated'=>$this->o,'totalVulnerabilities'=>$this->v]; }
        public function findById(Uuid $id): ?\App\Dependency\Domain\Model\Dependency { return null; }
        public function findByName(string $name, string $packageManager): ?\App\Dependency\Domain\Model\Dependency { return null; }
        public function findByProjectId(Uuid $projectId): array { return []; }
        public function save(\App\Dependency\Domain\Model\Dependency $dependency): void {}
        public function delete(\App\Dependency\Domain\Model\Dependency $dependency): void {}
        public function findFiltered(array $filters, int $page, int $perPage, string $sort, string $sortDir): array { return []; }
        public function countFiltered(array $filters): int { return 0; }
        public function findUniquePackages(): array { return []; }
        public function getStatsSingle(array $filters = []): array { return ['total'=>0,'outdated'=>0,'totalVulnerabilities'=>0]; }
    };
}

function stubProjectRepoRecent(array $projects): ProjectRepositoryInterface
{
    return new class ($projects) implements ProjectRepositoryInterface {
        public function __construct(private array $projects) {}
        public function findRecentlySynced(int $limit = 5): array { return \array_slice($this->projects, 0, $limit); }
        // unused
        public function findById(Uuid $id): ?Project { return null; }
        public function findBySlug(string $slug): ?Project { return null; }
        public function findByExternalIdAndProvider(string $externalId, Uuid $providerId): ?Project { return null; }
        public function findExternalIdMapByProvider(Uuid $providerId): array { return []; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function findByProviderId(Uuid $providerId): array { return []; }
        public function findAllWithProvider(): array { return []; }
        public function save(Project $project): void {}
        public function delete(Project $project): void {}
        public function count(): int { return 0; }
    };
}

function makeProject(string $name, string $slug, bool $synced = true): Project
{
    $p = Project::create(name: $name, slug: $slug, description: null, repositoryUrl: 'https://x/' . $slug, defaultBranch: 'main', visibility: ProjectVisibility::Private, ownerId: Uuid::v7());
    if ($synced) {
        $p->markSynced();
    }
    return $p;
}

describe('GetDashboardHandler', function () {
    it('returns 4 KPI cards built from current stats', function () {
        $handler = new GetDashboardHandler(
            stubDepRepoStatsHandler(total: 100, outdated: 30, vulns: 5),
            stubGlobalSnapshotRepo(null, null),
            stubProjectSnapshotRepo([], []),
            stubProjectRepoRecent([]),
        );

        $result = $handler(new GetDashboardQuery('user-1', '30d'));

        expect($result)->toBeInstanceOf(DashboardOutput::class);
        expect($result->metrics)->toHaveCount(4);
        expect($result->metrics[0]->label)->toBe('total');
        expect($result->metrics[0]->value)->toBe(100);
        expect($result->metrics[1]->label)->toBe('upToDate');
        expect($result->metrics[1]->value)->toBe(70);
        expect($result->metrics[2]->label)->toBe('outdated');
        expect($result->metrics[2]->value)->toBe(30);
        expect($result->metrics[3]->label)->toBe('vulnerabilities');
        expect($result->metrics[3]->value)->toBe(5);
    });

    it('computes change vs previous snapshot when available', function () {
        $previous = DependencyStatsSnapshot::create(80, 60, 20, 4);
        $latest = DependencyStatsSnapshot::create(100, 70, 30, 5);

        $handler = new GetDashboardHandler(
            stubDepRepoStatsHandler(100, 30, 5),
            stubGlobalSnapshotRepo($latest, $previous),
            stubProjectSnapshotRepo([], []),
            stubProjectRepoRecent([]),
        );

        $result = $handler(new GetDashboardQuery('user-1', '30d'));

        expect($result->metrics[0]->change)->toBe(25.0);
        expect($result->metrics[1]->change)->toBeGreaterThan(16.0);
        expect($result->metrics[1]->change)->toBeLessThan(17.0);
        expect($result->metrics[2]->change)->toBe(50.0);
        expect($result->metrics[3]->change)->toBe(25.0);
    });

    it('returns null change when no previous snapshot exists', function () {
        $latest = DependencyStatsSnapshot::create(100, 70, 30, 5);
        $handler = new GetDashboardHandler(
            stubDepRepoStatsHandler(100, 30, 5),
            stubGlobalSnapshotRepo($latest, null),
            stubProjectSnapshotRepo([], []),
            stubProjectRepoRecent([]),
        );

        $result = $handler(new GetDashboardQuery('user-1', '30d'));

        expect($result->metrics[0]->change)->toBeNull();
    });

    it('builds history with computed ratios from snapshots in range', function () {
        $s1 = DependencyStatsSnapshot::create(100, 70, 30, 0);
        $s2 = DependencyStatsSnapshot::create(110, 88, 22, 0);

        $handler = new GetDashboardHandler(
            stubDepRepoStatsHandler(110, 22, 0),
            stubGlobalSnapshotRepo($s2, $s1, [$s1, $s2]),
            stubProjectSnapshotRepo([], []),
            stubProjectRepoRecent([]),
        );

        $result = $handler(new GetDashboardQuery('user-1', '30d'));

        expect($result->history)->toHaveCount(2);
        expect($result->history[0])->toBeInstanceOf(DashboardSnapshotPoint::class);
        expect($result->history[0]->totalCount)->toBe(100);
        expect($result->history[0]->upToDateRatio)->toBe(70.0);
        expect($result->history[1]->upToDateRatio)->toBe(80.0);
    });

    it('handles zero total in ratio computation', function () {
        $s = DependencyStatsSnapshot::create(0, 0, 0, 0);
        $handler = new GetDashboardHandler(
            stubDepRepoStatsHandler(0, 0, 0),
            stubGlobalSnapshotRepo($s, null, [$s]),
            stubProjectSnapshotRepo([], []),
            stubProjectRepoRecent([]),
        );

        $result = $handler(new GetDashboardQuery('user-1', '30d'));

        expect($result->history[0]->upToDateRatio)->toBe(0.0);
    });

    it('returns recent projects sorted with delta vs previous snapshot', function () {
        $projectA = makeProject('Alpha', 'alpha');
        $projectB = makeProject('Bravo', 'bravo');
        $aId = $projectA->getId();
        $bId = $projectB->getId();

        $latestA = ProjectDependencyStatsSnapshot::create($aId, 50, 45, 5, 0);
        $previousA = ProjectDependencyStatsSnapshot::create($aId, 50, 40, 10, 0);
        $latestB = ProjectDependencyStatsSnapshot::create($bId, 30, 20, 10, 1);
        // no previous for B

        $handler = new GetDashboardHandler(
            stubDepRepoStatsHandler(80, 15, 1),
            stubGlobalSnapshotRepo(null, null),
            stubProjectSnapshotRepo(
                latest: [$aId->toRfc4122() => $latestA, $bId->toRfc4122() => $latestB],
                previous: [$aId->toRfc4122() => $previousA],
            ),
            stubProjectRepoRecent([$projectA, $projectB]),
        );

        $result = $handler(new GetDashboardQuery('user-1', '30d'));

        expect($result->recentProjects)->toHaveCount(2);
        expect($result->recentProjects[0])->toBeInstanceOf(DashboardRecentProject::class);
        expect($result->recentProjects[0]->slug)->toBe('alpha');
        expect($result->recentProjects[0]->totalDependencies)->toBe(50);
        expect($result->recentProjects[0]->upToDateCount)->toBe(45);
        expect($result->recentProjects[0]->upToDateRatio)->toBe(90.0);
        expect($result->recentProjects[0]->deltaSinceLastSync)->toBe(5);

        expect($result->recentProjects[1]->slug)->toBe('bravo');
        expect($result->recentProjects[1]->deltaSinceLastSync)->toBe(0);
    });

    it('returns empty arrays when no snapshots and no recent projects', function () {
        $handler = new GetDashboardHandler(
            stubDepRepoStatsHandler(0, 0, 0),
            stubGlobalSnapshotRepo(null, null),
            stubProjectSnapshotRepo([], []),
            stubProjectRepoRecent([]),
        );

        $result = $handler(new GetDashboardQuery('user-1', '30d'));

        expect($result->history)->toBe([]);
        expect($result->recentProjects)->toBe([]);
    });
});
```

- [ ] **Step 2: Run test, verify failure**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Activity/Application/QueryHandler/GetDashboardHandlerTest.php
```

Expected: FAIL on multiple tests — handler signature does not yet accept these constructor args.

- [ ] **Step 3: Replace `GetDashboardHandler.php`**

```php
<?php

declare(strict_types=1);

namespace App\Activity\Application\QueryHandler;

use App\Activity\Application\DTO\DashboardMetric;
use App\Activity\Application\DTO\DashboardOutput;
use App\Activity\Application\DTO\DashboardRecentProject;
use App\Activity\Application\DTO\DashboardSnapshotPoint;
use App\Activity\Application\Query\GetDashboardQuery;
use App\Activity\Domain\Model\DependencyStatsSnapshot;
use App\Activity\Domain\Model\ProjectDependencyStatsSnapshot;
use App\Activity\Domain\Repository\DependencyStatsSnapshotRepositoryInterface;
use App\Activity\Domain\Repository\ProjectDependencyStatsSnapshotRepositoryInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetDashboardHandler
{
    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
        private DependencyStatsSnapshotRepositoryInterface $globalSnapshotRepository,
        private ProjectDependencyStatsSnapshotRepositoryInterface $projectSnapshotRepository,
        private ProjectRepositoryInterface $projectRepository,
    ) {
    }

    public function __invoke(GetDashboardQuery $query): DashboardOutput
    {
        $stats = $this->dependencyRepository->getStats([]);
        $total = (int) ($stats['total'] ?? 0);
        $outdated = (int) ($stats['outdated'] ?? 0);
        $vulnerabilities = (int) ($stats['totalVulnerabilities'] ?? 0);
        $upToDate = \max(0, $total - $outdated);

        $latest = $this->globalSnapshotRepository->findLatest();
        $previous = $this->globalSnapshotRepository->findPrevious();

        $metrics = [
            new DashboardMetric('total', $total, $this->changePct($latest?->getTotalCount(), $previous?->getTotalCount())),
            new DashboardMetric('upToDate', $upToDate, $this->changePct($latest?->getUpToDateCount(), $previous?->getUpToDateCount())),
            new DashboardMetric('outdated', $outdated, $this->changePct($latest?->getOutdatedCount(), $previous?->getOutdatedCount())),
            new DashboardMetric('vulnerabilities', $vulnerabilities, $this->changePct($latest?->getVulnerabilityCount(), $previous?->getVulnerabilityCount())),
        ];

        $history = $this->buildHistory($query->range);
        $recentProjects = $this->buildRecentProjects();

        return new DashboardOutput(
            metrics: $metrics,
            history: $history,
            recentProjects: $recentProjects,
        );
    }

    private function changePct(?int $current, ?int $previous): ?float
    {
        if ($current === null || $previous === null || $previous === 0) {
            return null;
        }

        return \round((($current - $previous) / $previous) * 100, 2);
    }

    /** @return list<DashboardSnapshotPoint> */
    private function buildHistory(string $range): array
    {
        $now = new DateTimeImmutable();
        $from = match ($range) {
            '7d' => $now->modify('-7 days'),
            '90d' => $now->modify('-90 days'),
            '1y' => $now->modify('-1 year'),
            default => $now->modify('-30 days'),
        };

        $snapshots = $this->globalSnapshotRepository->findInRange($from, $now);

        if (\in_array($range, ['90d', '1y'], true)) {
            $snapshots = $this->downsampleByDay($snapshots);
        }

        $points = [];
        foreach ($snapshots as $s) {
            $points[] = new DashboardSnapshotPoint(
                timestamp: $s->getCreatedAt()->format(\DATE_ATOM),
                totalCount: $s->getTotalCount(),
                upToDateCount: $s->getUpToDateCount(),
                outdatedCount: $s->getOutdatedCount(),
                upToDateRatio: $s->getTotalCount() > 0
                    ? \round(($s->getUpToDateCount() / $s->getTotalCount()) * 100, 2)
                    : 0.0,
            );
        }

        return $points;
    }

    /**
     * Keeps only the latest snapshot per calendar day.
     *
     * @param list<DependencyStatsSnapshot> $snapshots
     * @return list<DependencyStatsSnapshot>
     */
    private function downsampleByDay(array $snapshots): array
    {
        $byDay = [];
        foreach ($snapshots as $s) {
            $key = $s->getCreatedAt()->format('Y-m-d');
            if (!isset($byDay[$key]) || $s->getCreatedAt() > $byDay[$key]->getCreatedAt()) {
                $byDay[$key] = $s;
            }
        }

        \ksort($byDay);

        return \array_values($byDay);
    }

    /** @return list<DashboardRecentProject> */
    private function buildRecentProjects(): array
    {
        $projects = $this->projectRepository->findRecentlySynced(5);
        if ($projects === []) {
            return [];
        }

        $ids = \array_map(static fn ($p) => $p->getId(), $projects);
        $latests = $this->projectSnapshotRepository->findLatestForProjects($ids);
        $previouses = $this->projectSnapshotRepository->findPreviousForProjects($ids);

        $output = [];
        foreach ($projects as $project) {
            $key = $project->getId()->toRfc4122();
            $latest = $latests[$key] ?? null;
            $previous = $previouses[$key] ?? null;

            $total = $latest?->getTotalCount() ?? 0;
            $upToDate = $latest?->getUpToDateCount() ?? 0;
            $outdated = $latest?->getOutdatedCount() ?? 0;
            $ratio = $total > 0 ? \round(($upToDate / $total) * 100, 2) : 0.0;
            $delta = $latest !== null && $previous !== null
                ? $latest->getUpToDateCount() - $previous->getUpToDateCount()
                : 0;

            $output[] = new DashboardRecentProject(
                projectId: $key,
                name: $project->getName(),
                slug: $project->getSlug(),
                lastSyncedAt: $project->getLastSyncedAt()?->format(\DATE_ATOM),
                totalDependencies: $total,
                upToDateCount: $upToDate,
                outdatedCount: $outdated,
                upToDateRatio: $ratio,
                deltaSinceLastSync: $delta,
            );
        }

        return $output;
    }
}
```

- [ ] **Step 4: Run test, verify pass**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Activity/Application/QueryHandler/GetDashboardHandlerTest.php
```

Expected: PASS (7 tests).

- [ ] **Step 5: Run the full backend test suite**

```
make test-backend
```

Expected: 0 failures. Note: the existing functional `DashboardEndpointsTest` may need adjustment (it asserts `metrics empty` — that will become 4 cards with values). We update that in Task 14.

- [ ] **Step 6: Lint + commit**

```
make fix-backend && make lint-backend
git add backend/src/Activity/Application/QueryHandler/GetDashboardHandler.php backend/tests/Unit/Activity/Application/QueryHandler/GetDashboardHandlerTest.php
git commit -m "feat(activity): rewrite GetDashboardHandler with kpi history and recent projects"
```

---

## Task 14: Controller range param + functional endpoint test update

**Files:**
- Modify: `backend/src/Activity/Presentation/Controller/GetDashboardController.php`
- Modify: `backend/tests/Functional/Activity/DashboardEndpointsTest.php`

- [ ] **Step 1: Update the controller to read `range`**

Replace `__invoke` in `GetDashboardController.php` with:

```php
    public function __invoke(\Symfony\Component\HttpFoundation\Request $request, #[CurrentUser] UserInterface $user): JsonResponse
    {
        $range = (string) $request->query->get('range', '30d');
        if (!\in_array($range, GetDashboardQuery::VALID_RANGES, true)) {
            $range = '30d';
        }

        $envelope = $this->queryBus->dispatch(
            new GetDashboardQuery($user->getUserIdentifier(), $range),
        );
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray());
    }
```

Add the `Request` use statement at the top:

```php
use Symfony\Component\HttpFoundation\Request;
```

- [ ] **Step 2: Update the functional test**

Replace `backend/tests/Functional/Activity/DashboardEndpointsTest.php` with:

```php
<?php

declare(strict_types=1);

use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->client = static::createClient();
    $this->resetDatabase();
    $auth = $this->createAuthenticatedUser();
    $this->user = $auth['user'];
    $this->token = $auth['token'];
});

describe('GET /api/v1/activity/dashboard', function () {
    it('returns dashboard data with the new structure for an authenticated user', function () {
        $this->client->request('GET', '/api/v1/activity/dashboard', [], [], $this->authHeader($this->token));

        $response = $this->client->getResponse();
        expect($response->getStatusCode())->toBe(200);

        $body = \json_decode($response->getContent(), true);
        expect($body['success'])->toBeTrue();
        expect($body['data'])->toBeArray();
        expect($body['data']['metrics'])->toBeArray();
        expect($body['data']['metrics'])->toHaveCount(4);
        expect($body['data']['metrics'][0])->toHaveKey('label');
        expect($body['data']['metrics'][0])->toHaveKey('value');
        expect($body['data']['metrics'][0])->toHaveKey('change');
        expect($body['data']['history'])->toBeArray();
        expect($body['data']['recentProjects'])->toBeArray();
    });

    it('accepts a range query parameter', function () {
        $this->client->request('GET', '/api/v1/activity/dashboard?range=7d', [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
    });

    it('falls back to 30d on an invalid range', function () {
        $this->client->request('GET', '/api/v1/activity/dashboard?range=garbage', [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
    });

    it('returns 401 without authentication', function () {
        $this->client->request('GET', '/api/v1/activity/dashboard');

        expect($this->client->getResponse()->getStatusCode())->toBe(401);
    });
});
```

- [ ] **Step 3: Run the functional test**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Functional/Activity/DashboardEndpointsTest.php
```

Expected: PASS (4 tests).

- [ ] **Step 4: Restart messenger consumer (mandatory after backend changes)**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend php bin/console cache:clear --env=prod
docker compose -f docker/compose.yaml -f docker/compose.override.yaml restart messenger-consumer
```

- [ ] **Step 5: Full backend verification**

```
make fix-backend && make lint-backend && make test-backend
```

Expected: 0 errors anywhere.

- [ ] **Step 6: Commit**

```
git add backend/src/Activity/Presentation/Controller/GetDashboardController.php backend/tests/Functional/Activity/DashboardEndpointsTest.php
git commit -m "feat(activity): support range query param on dashboard endpoint"
```

---

## Task 15: Frontend types + service extension

**Files:**
- Create: `frontend/src/activity/types/dashboard.types.ts`
- Modify: `frontend/src/activity/services/dashboard.service.ts`

- [ ] **Step 1: Create the types file**

```ts
export type DashboardRange = '7d' | '30d' | '90d' | '1y';

export interface DashboardMetric {
  label: string;
  value: number | string;
  change: number | null;
}

export interface DashboardSnapshotPoint {
  timestamp: string;
  totalCount: number;
  upToDateCount: number;
  outdatedCount: number;
  upToDateRatio: number;
}

export interface DashboardRecentProject {
  projectId: string;
  name: string;
  slug: string;
  lastSyncedAt: string | null;
  totalDependencies: number;
  upToDateCount: number;
  outdatedCount: number;
  upToDateRatio: number;
  deltaSinceLastSync: number;
}

export interface DashboardData {
  metrics: DashboardMetric[];
  history: DashboardSnapshotPoint[];
  recentProjects: DashboardRecentProject[];
}
```

- [ ] **Step 2: Replace `dashboard.service.ts`**

```ts
import type { ApiResponse } from '@/shared/types';
import { api } from '@/shared/utils/api';
import type { DashboardData, DashboardRange } from '@/activity/types/dashboard.types';

export type {
  DashboardData,
  DashboardMetric,
  DashboardRange,
  DashboardRecentProject,
  DashboardSnapshotPoint,
} from '@/activity/types/dashboard.types';

export const dashboardService = {
  getDashboard(range: DashboardRange = '30d'): Promise<ApiResponse<DashboardData>> {
    return api.get<ApiResponse<DashboardData>>(`/activity/dashboard?range=${range}`);
  },
};
```

- [ ] **Step 3: Lint frontend**

```
make lint-frontend
```

Expected: 0 errors.

- [ ] **Step 4: Commit**

```
git add frontend/src/activity/types/dashboard.types.ts frontend/src/activity/services/dashboard.service.ts
git commit -m "feat(dashboard): add typed dashboard service with range support"
```

---

## Task 16: Frontend store extension

**Files:**
- Modify: `frontend/src/activity/stores/dashboard.ts`
- Test: `frontend/tests/unit/activity/stores/dashboard.test.ts` (extend existing)

- [ ] **Step 1: Replace the store**

```ts
import { ref } from 'vue';
import { defineStore } from 'pinia';
import { dashboardService } from '@/activity/services/dashboard.service';
import type {
  DashboardMetric,
  DashboardRange,
  DashboardRecentProject,
  DashboardSnapshotPoint,
} from '@/activity/types/dashboard.types';
import { i18n } from '@/shared/i18n';

export type { DashboardMetric };

export const useDashboardStore = defineStore('dashboard', () => {
  const t = i18n.global.t;
  const metrics = ref<DashboardMetric[]>([]);
  const history = ref<DashboardSnapshotPoint[]>([]);
  const recentProjects = ref<DashboardRecentProject[]>([]);
  const range = ref<DashboardRange>('30d');
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function load(newRange?: DashboardRange): Promise<void> {
    if (newRange) range.value = newRange;
    loading.value = true;
    error.value = null;

    try {
      const response = await dashboardService.getDashboard(range.value);
      metrics.value = response.data.metrics;
      history.value = response.data.history;
      recentProjects.value = response.data.recentProjects;
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.dashboard') });
    } finally {
      loading.value = false;
    }
  }

  async function setRange(newRange: DashboardRange): Promise<void> {
    range.value = newRange;
    await load();
  }

  return {
    error,
    history,
    load,
    loading,
    metrics,
    range,
    recentProjects,
    setRange,
  };
});
```

- [ ] **Step 2: Read the existing store test**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend cat tests/unit/activity/stores/dashboard.test.ts
```

Note its structure (mocks, beforeEach setup).

- [ ] **Step 3: Replace it with extended tests**

Overwrite `frontend/tests/unit/activity/stores/dashboard.test.ts`:

```ts
import { setActivePinia, createPinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { useDashboardStore } from '@/activity/stores/dashboard';
import { dashboardService } from '@/activity/services/dashboard.service';

vi.mock('@/activity/services/dashboard.service', () => ({
  dashboardService: { getDashboard: vi.fn() },
}));

const mocked = vi.mocked(dashboardService);

beforeEach(() => {
  setActivePinia(createPinia());
  vi.clearAllMocks();
});

describe('useDashboardStore', () => {
  it('loads metrics, history and recent projects', async () => {
    mocked.getDashboard.mockResolvedValue({
      success: true,
      data: {
        metrics: [{ label: 'total', value: 100, change: 5 }],
        history: [{ timestamp: '2026-04-06T08:00:00+00:00', totalCount: 100, upToDateCount: 70, outdatedCount: 30, upToDateRatio: 70 }],
        recentProjects: [{ projectId: 'p-1', name: 'A', slug: 'a', lastSyncedAt: '2026-04-06T08:00:00+00:00', totalDependencies: 50, upToDateCount: 45, outdatedCount: 5, upToDateRatio: 90, deltaSinceLastSync: 3 }],
      },
    } as never);

    const store = useDashboardStore();
    await store.load();

    expect(store.metrics).toHaveLength(1);
    expect(store.history).toHaveLength(1);
    expect(store.recentProjects).toHaveLength(1);
    expect(mocked.getDashboard).toHaveBeenCalledWith('30d');
  });

  it('setRange updates the range and refetches', async () => {
    mocked.getDashboard.mockResolvedValue({
      success: true,
      data: { metrics: [], history: [], recentProjects: [] },
    } as never);

    const store = useDashboardStore();
    await store.setRange('90d');

    expect(store.range).toBe('90d');
    expect(mocked.getDashboard).toHaveBeenCalledWith('90d');
  });

  it('sets error message on failure', async () => {
    mocked.getDashboard.mockRejectedValue(new Error('boom'));

    const store = useDashboardStore();
    await store.load();

    expect(store.error).not.toBeNull();
  });
});
```

- [ ] **Step 4: Run frontend tests**

```
make test-frontend
```

Expected: PASS for `dashboard.test.ts`.

- [ ] **Step 5: Commit**

```
git add frontend/src/activity/stores/dashboard.ts frontend/tests/unit/activity/stores/dashboard.test.ts
git commit -m "feat(dashboard): extend store with history range and recent projects"
```

---

## Task 17: `DashboardKpiCard` component

**Files:**
- Create: `frontend/src/activity/components/DashboardKpiCard.vue`
- Test: `frontend/tests/unit/activity/components/DashboardKpiCard.test.ts`

- [ ] **Step 1: Write the failing test**

```ts
import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import DashboardKpiCard from '@/activity/components/DashboardKpiCard.vue';

describe('DashboardKpiCard', () => {
  it('renders label and value', () => {
    const wrapper = mount(DashboardKpiCard, {
      props: { label: 'Total', value: 1247 },
    });
    expect(wrapper.text()).toContain('Total');
    expect(wrapper.text()).toContain('1,247');
  });

  it('shows green delta when positive (default direction)', () => {
    const wrapper = mount(DashboardKpiCard, {
      props: { label: 'X', value: 100, delta: 5 },
    });
    const delta = wrapper.find('[data-testid="kpi-delta"]');
    expect(delta.classes()).toContain('text-success');
  });

  it('shows red delta when negative', () => {
    const wrapper = mount(DashboardKpiCard, {
      props: { label: 'X', value: 100, delta: -3 },
    });
    const delta = wrapper.find('[data-testid="kpi-delta"]');
    expect(delta.classes()).toContain('text-danger');
  });

  it('inverts color logic when deltaInverted is true', () => {
    const wrapper = mount(DashboardKpiCard, {
      props: { label: 'Vulnerabilities', value: 12, delta: 5, deltaInverted: true },
    });
    const delta = wrapper.find('[data-testid="kpi-delta"]');
    expect(delta.classes()).toContain('text-danger');
  });

  it('hides delta when null', () => {
    const wrapper = mount(DashboardKpiCard, {
      props: { label: 'X', value: 100, delta: null },
    });
    expect(wrapper.find('[data-testid="kpi-delta"]').exists()).toBe(false);
  });
});
```

- [ ] **Step 2: Run test, verify failure**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm vitest run tests/unit/activity/components/DashboardKpiCard.test.ts
```

Expected: FAIL, component not found.

- [ ] **Step 3: Create the component**

```vue
<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    label: string;
    value: number | string;
    delta?: number | null;
    deltaInverted?: boolean;
  }>(),
  { delta: null, deltaInverted: false },
);

const formattedValue = computed(() =>
  typeof props.value === 'number' ? props.value.toLocaleString('en-US') : props.value,
);

const deltaIsPositive = computed(() => (props.delta ?? 0) >= 0);
const isGood = computed(() => (props.deltaInverted ? !deltaIsPositive.value : deltaIsPositive.value));
</script>

<template>
  <div class="rounded-xl border border-border bg-surface p-5 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-wider text-text-muted">
      {{ label }}
    </p>
    <p class="mt-2 text-3xl font-bold text-text">{{ formattedValue }}</p>
    <p
      v-if="delta !== null && delta !== undefined"
      data-testid="kpi-delta"
      class="mt-2 text-sm font-medium"
      :class="isGood ? 'text-success' : 'text-danger'"
    >
      {{ deltaIsPositive ? '↑' : '↓' }} {{ Math.abs(delta) }}%
    </p>
  </div>
</template>
```

- [ ] **Step 4: Run test, verify pass**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm vitest run tests/unit/activity/components/DashboardKpiCard.test.ts
```

Expected: PASS (5 tests).

- [ ] **Step 5: Lint + commit**

```
make lint-frontend
git add frontend/src/activity/components/DashboardKpiCard.vue frontend/tests/unit/activity/components/DashboardKpiCard.test.ts
git commit -m "feat(dashboard): add DashboardKpiCard component"
```

---

## Task 18: `DashboardEvolutionChart` component

**Files:**
- Create: `frontend/src/activity/components/DashboardEvolutionChart.vue`
- Test: `frontend/tests/unit/activity/components/DashboardEvolutionChart.test.ts`

- [ ] **Step 1: Write the failing test**

```ts
import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import DashboardEvolutionChart from '@/activity/components/DashboardEvolutionChart.vue';
import type { DashboardSnapshotPoint } from '@/activity/types/dashboard.types';

const points: DashboardSnapshotPoint[] = [
  { timestamp: '2026-04-01T00:00:00+00:00', totalCount: 100, upToDateCount: 70, outdatedCount: 30, upToDateRatio: 70 },
  { timestamp: '2026-04-02T00:00:00+00:00', totalCount: 100, upToDateCount: 80, outdatedCount: 20, upToDateRatio: 80 },
  { timestamp: '2026-04-03T00:00:00+00:00', totalCount: 100, upToDateCount: 90, outdatedCount: 10, upToDateRatio: 90 },
];

describe('DashboardEvolutionChart', () => {
  it('renders an SVG path when points are provided', () => {
    const wrapper = mount(DashboardEvolutionChart, {
      props: { points, range: '30d' },
    });
    expect(wrapper.find('svg').exists()).toBe(true);
    expect(wrapper.find('path[data-testid="chart-line"]').exists()).toBe(true);
  });

  it('shows the empty state when there are fewer than 2 points', () => {
    const wrapper = mount(DashboardEvolutionChart, {
      props: { points: [], range: '30d' },
    });
    expect(wrapper.find('[data-testid="chart-empty"]').exists()).toBe(true);
  });

  it('renders the 4 range buttons', () => {
    const wrapper = mount(DashboardEvolutionChart, {
      props: { points, range: '30d' },
    });
    expect(wrapper.findAll('[data-testid^="range-btn-"]')).toHaveLength(4);
  });

  it('highlights the currently selected range', () => {
    const wrapper = mount(DashboardEvolutionChart, {
      props: { points, range: '90d' },
    });
    const active = wrapper.find('[data-testid="range-btn-90d"]');
    expect(active.classes()).toContain('bg-primary');
  });

  it('emits update:range when a range button is clicked', async () => {
    const wrapper = mount(DashboardEvolutionChart, {
      props: { points, range: '30d' },
    });
    await wrapper.find('[data-testid="range-btn-7d"]').trigger('click');
    expect(wrapper.emitted('update:range')).toEqual([['7d']]);
  });
});
```

- [ ] **Step 2: Run test, verify failure**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm vitest run tests/unit/activity/components/DashboardEvolutionChart.test.ts
```

Expected: FAIL, component not found.

- [ ] **Step 3: Create the component**

```vue
<script setup lang="ts">
import { computed, ref } from 'vue';

import type {
  DashboardRange,
  DashboardSnapshotPoint,
} from '@/activity/types/dashboard.types';

const props = defineProps<{
  points: DashboardSnapshotPoint[];
  range: DashboardRange;
}>();

const emit = defineEmits<{ 'update:range': [range: DashboardRange] }>();

const ranges: DashboardRange[] = ['7d', '30d', '90d', '1y'];

const VIEW_WIDTH = 800;
const VIEW_HEIGHT = 240;

const hoveredIndex = ref<number | null>(null);

const coords = computed(() => {
  if (props.points.length < 2) return [];
  const ratios = props.points.map((p) => p.upToDateRatio);
  const min = Math.max(0, Math.min(...ratios) - 5);
  const max = Math.min(100, Math.max(...ratios) + 5);
  const span = Math.max(1, max - min);
  const step = VIEW_WIDTH / (props.points.length - 1);
  return props.points.map((p, i) => ({
    x: i * step,
    y: VIEW_HEIGHT - ((p.upToDateRatio - min) / span) * VIEW_HEIGHT,
    point: p,
  }));
});

const linePath = computed(() => {
  if (coords.value.length === 0) return '';
  return coords.value.map((c, i) => `${i === 0 ? 'M' : 'L'}${c.x.toFixed(1)},${c.y.toFixed(1)}`).join(' ');
});

const areaPath = computed(() => {
  if (coords.value.length === 0) return '';
  const line = linePath.value;
  const last = coords.value[coords.value.length - 1];
  return `${line} L${last.x.toFixed(1)},${VIEW_HEIGHT} L0,${VIEW_HEIGHT} Z`;
});

const hovered = computed(() => (hoveredIndex.value !== null ? coords.value[hoveredIndex.value] : null));

function onMove(event: MouseEvent): void {
  const svg = event.currentTarget as SVGSVGElement;
  const rect = svg.getBoundingClientRect();
  const x = ((event.clientX - rect.left) / rect.width) * VIEW_WIDTH;
  let nearestIndex = 0;
  let nearestDist = Number.POSITIVE_INFINITY;
  coords.value.forEach((c, i) => {
    const d = Math.abs(c.x - x);
    if (d < nearestDist) {
      nearestDist = d;
      nearestIndex = i;
    }
  });
  hoveredIndex.value = nearestIndex;
}

function onLeave(): void {
  hoveredIndex.value = null;
}

function selectRange(r: DashboardRange): void {
  emit('update:range', r);
}
</script>

<template>
  <div class="rounded-xl border border-border bg-surface p-5">
    <div class="mb-4 flex items-center justify-between">
      <div>
        <h3 class="text-base font-semibold text-text">Évolution des mises à jour</h3>
        <p class="text-xs text-text-muted">% de dépendances à jour dans le catalogue</p>
      </div>
      <div class="flex gap-1 rounded-lg border border-border bg-background p-1">
        <button
          v-for="r in ranges"
          :key="r"
          :data-testid="`range-btn-${r}`"
          type="button"
          class="rounded px-3 py-1 text-xs font-medium transition"
          :class="r === range ? 'bg-primary text-white' : 'text-text-muted hover:text-text'"
          @click="selectRange(r)"
        >
          {{ r }}
        </button>
      </div>
    </div>

    <div v-if="points.length < 2" data-testid="chart-empty" class="flex h-60 items-center justify-center text-sm text-text-muted">
      Pas assez de données pour afficher l'évolution. Lancez un sync.
    </div>

    <div v-else class="relative h-60">
      <svg
        :viewBox="`0 0 ${VIEW_WIDTH} ${VIEW_HEIGHT}`"
        preserveAspectRatio="none"
        class="h-full w-full"
        @mousemove="onMove"
        @mouseleave="onLeave"
      >
        <defs>
          <linearGradient id="evoGradient" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="currentColor" stop-opacity="0.3" />
            <stop offset="100%" stop-color="currentColor" stop-opacity="0" />
          </linearGradient>
        </defs>
        <path :d="areaPath" fill="url(#evoGradient)" class="text-primary" />
        <path data-testid="chart-line" :d="linePath" stroke="currentColor" stroke-width="2.5" fill="none" class="text-primary" />
        <circle v-if="hovered" :cx="hovered.x" :cy="hovered.y" r="5" class="fill-primary stroke-surface" stroke-width="2" />
      </svg>
      <div
        v-if="hovered"
        data-testid="chart-tooltip"
        class="pointer-events-none absolute rounded border border-border bg-background px-3 py-2 text-xs shadow-lg"
        :style="{ left: `${(hovered.x / VIEW_WIDTH) * 100}%`, top: '4px' }"
      >
        <div class="text-text-muted">{{ new Date(hovered.point.timestamp).toLocaleDateString() }}</div>
        <div class="text-base font-bold text-text">{{ hovered.point.upToDateRatio.toFixed(1) }}%</div>
        <div class="text-text-muted">{{ hovered.point.upToDateCount }} / {{ hovered.point.totalCount }} à jour</div>
      </div>
    </div>
  </div>
</template>
```

- [ ] **Step 4: Run test, verify pass**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm vitest run tests/unit/activity/components/DashboardEvolutionChart.test.ts
```

Expected: PASS (5 tests).

- [ ] **Step 5: Lint + commit**

```
make lint-frontend
git add frontend/src/activity/components/DashboardEvolutionChart.vue frontend/tests/unit/activity/components/DashboardEvolutionChart.test.ts
git commit -m "feat(dashboard): add DashboardEvolutionChart with SVG line and range selector"
```

---

## Task 19: `DashboardTotalSparkline` component

**Files:**
- Create: `frontend/src/activity/components/DashboardTotalSparkline.vue`
- Test: `frontend/tests/unit/activity/components/DashboardTotalSparkline.test.ts`

- [ ] **Step 1: Write the failing test**

```ts
import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import DashboardTotalSparkline from '@/activity/components/DashboardTotalSparkline.vue';
import type { DashboardSnapshotPoint } from '@/activity/types/dashboard.types';

const points: DashboardSnapshotPoint[] = [
  { timestamp: '2026-04-01T00:00:00+00:00', totalCount: 100, upToDateCount: 70, outdatedCount: 30, upToDateRatio: 70 },
  { timestamp: '2026-04-02T00:00:00+00:00', totalCount: 110, upToDateCount: 80, outdatedCount: 30, upToDateRatio: 72.7 },
  { timestamp: '2026-04-03T00:00:00+00:00', totalCount: 120, upToDateCount: 90, outdatedCount: 30, upToDateRatio: 75 },
];

describe('DashboardTotalSparkline', () => {
  it('renders the current total and trend delta', () => {
    const wrapper = mount(DashboardTotalSparkline, { props: { points } });
    expect(wrapper.text()).toContain('120');
    expect(wrapper.text()).toContain('+20');
  });

  it('renders an SVG path when points are present', () => {
    const wrapper = mount(DashboardTotalSparkline, { props: { points } });
    expect(wrapper.find('path[data-testid="sparkline-line"]').exists()).toBe(true);
  });

  it('shows nothing meaningful when empty', () => {
    const wrapper = mount(DashboardTotalSparkline, { props: { points: [] } });
    expect(wrapper.text()).toContain('—');
  });
});
```

- [ ] **Step 2: Run test, verify failure**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm vitest run tests/unit/activity/components/DashboardTotalSparkline.test.ts
```

Expected: FAIL.

- [ ] **Step 3: Create the component**

```vue
<script setup lang="ts">
import { computed } from 'vue';

import type { DashboardSnapshotPoint } from '@/activity/types/dashboard.types';

const props = defineProps<{ points: DashboardSnapshotPoint[] }>();

const VIEW_WIDTH = 400;
const VIEW_HEIGHT = 50;

const current = computed(() => {
  if (props.points.length === 0) return null;
  return props.points[props.points.length - 1].totalCount;
});

const delta = computed(() => {
  if (props.points.length < 2) return 0;
  return props.points[props.points.length - 1].totalCount - props.points[0].totalCount;
});

const linePath = computed(() => {
  if (props.points.length < 2) return '';
  const totals = props.points.map((p) => p.totalCount);
  const min = Math.min(...totals);
  const max = Math.max(...totals);
  const span = Math.max(1, max - min);
  const step = VIEW_WIDTH / (props.points.length - 1);
  return props.points
    .map((p, i) => {
      const x = i * step;
      const y = VIEW_HEIGHT - ((p.totalCount - min) / span) * VIEW_HEIGHT;
      return `${i === 0 ? 'M' : 'L'}${x.toFixed(1)},${y.toFixed(1)}`;
    })
    .join(' ');
});
</script>

<template>
  <div class="rounded-xl border border-border bg-surface p-5">
    <div class="flex items-end justify-between">
      <div>
        <h3 class="text-base font-semibold text-text">Total dépendances</h3>
        <p class="text-xs text-text-muted">Volume cumulé</p>
      </div>
      <div class="text-right">
        <div class="text-2xl font-bold text-text">{{ current ?? '—' }}</div>
        <div v-if="current !== null" class="text-xs text-success">
          {{ delta >= 0 ? '+' : '' }}{{ delta }}
        </div>
      </div>
    </div>
    <svg v-if="points.length >= 2" :viewBox="`0 0 ${VIEW_WIDTH} ${VIEW_HEIGHT}`" preserveAspectRatio="none" class="mt-3 h-12 w-full">
      <path data-testid="sparkline-line" :d="linePath" stroke="currentColor" stroke-width="2" fill="none" class="text-text-muted" />
    </svg>
  </div>
</template>
```

- [ ] **Step 4: Run test, verify pass**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm vitest run tests/unit/activity/components/DashboardTotalSparkline.test.ts
```

Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```
make lint-frontend
git add frontend/src/activity/components/DashboardTotalSparkline.vue frontend/tests/unit/activity/components/DashboardTotalSparkline.test.ts
git commit -m "feat(dashboard): add DashboardTotalSparkline component"
```

---

## Task 20: `DashboardRecentProjectsList` component

**Files:**
- Create: `frontend/src/activity/components/DashboardRecentProjectsList.vue`
- Test: `frontend/tests/unit/activity/components/DashboardRecentProjectsList.test.ts`

- [ ] **Step 1: Write the failing test**

```ts
import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import DashboardRecentProjectsList from '@/activity/components/DashboardRecentProjectsList.vue';
import type { DashboardRecentProject } from '@/activity/types/dashboard.types';

const projects: DashboardRecentProject[] = [
  { projectId: 'p-1', name: 'Alpha', slug: 'alpha', lastSyncedAt: '2026-04-06T08:00:00+00:00', totalDependencies: 50, upToDateCount: 46, outdatedCount: 4, upToDateRatio: 92, deltaSinceLastSync: 3 },
  { projectId: 'p-2', name: 'Bravo', slug: 'bravo', lastSyncedAt: '2026-04-06T07:00:00+00:00', totalDependencies: 30, upToDateCount: 22, outdatedCount: 8, upToDateRatio: 73.3, deltaSinceLastSync: -2 },
  { projectId: 'p-3', name: 'Charlie', slug: 'charlie', lastSyncedAt: null, totalDependencies: 20, upToDateCount: 10, outdatedCount: 10, upToDateRatio: 50, deltaSinceLastSync: 0 },
];

const pushSpy = vi.fn();
vi.mock('vue-router', () => ({ useRouter: () => ({ push: pushSpy }) }));

describe('DashboardRecentProjectsList', () => {
  it('renders one card per project', () => {
    const wrapper = mount(DashboardRecentProjectsList, { props: { projects } });
    expect(wrapper.findAll('[data-testid="recent-project"]')).toHaveLength(3);
  });

  it('uses the good color when ratio >= 85', () => {
    const wrapper = mount(DashboardRecentProjectsList, { props: { projects } });
    const bar = wrapper.findAll('[data-testid="recent-project"]')[0].find('[data-testid="ratio-fill"]');
    expect(bar.classes()).toContain('bg-success');
  });

  it('uses the warn color when ratio between 70 and 85', () => {
    const wrapper = mount(DashboardRecentProjectsList, { props: { projects } });
    const bar = wrapper.findAll('[data-testid="recent-project"]')[1].find('[data-testid="ratio-fill"]');
    expect(bar.classes()).toContain('bg-warning');
  });

  it('uses the bad color when ratio < 70', () => {
    const wrapper = mount(DashboardRecentProjectsList, { props: { projects } });
    const bar = wrapper.findAll('[data-testid="recent-project"]')[2].find('[data-testid="ratio-fill"]');
    expect(bar.classes()).toContain('bg-danger');
  });

  it('shows a green delta for positive', () => {
    const wrapper = mount(DashboardRecentProjectsList, { props: { projects } });
    const delta = wrapper.findAll('[data-testid="recent-project"]')[0].find('[data-testid="recent-delta"]');
    expect(delta.classes()).toContain('text-success');
  });

  it('shows a red delta for negative', () => {
    const wrapper = mount(DashboardRecentProjectsList, { props: { projects } });
    const delta = wrapper.findAll('[data-testid="recent-project"]')[1].find('[data-testid="recent-delta"]');
    expect(delta.classes()).toContain('text-danger');
  });

  it('navigates to the project detail page on click', async () => {
    const wrapper = mount(DashboardRecentProjectsList, { props: { projects } });
    await wrapper.findAll('[data-testid="recent-project"]')[0].trigger('click');
    expect(pushSpy).toHaveBeenCalledWith({ name: 'project-detail', params: { slug: 'alpha' } });
  });
});
```

If the actual project detail route name is different, find it before writing this test:

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend grep -rn "project-detail\|projectDetail" src/router src/catalog/router 2>/dev/null
```

Use the actual route name.

- [ ] **Step 2: Run test, verify failure**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm vitest run tests/unit/activity/components/DashboardRecentProjectsList.test.ts
```

Expected: FAIL.

- [ ] **Step 3: Create the component**

```vue
<script setup lang="ts">
import { useRouter } from 'vue-router';

import type { DashboardRecentProject } from '@/activity/types/dashboard.types';

defineProps<{ projects: DashboardRecentProject[] }>();

const router = useRouter();

function colorForRatio(r: number): string {
  if (r >= 85) return 'bg-success';
  if (r >= 70) return 'bg-warning';
  return 'bg-danger';
}

function deltaClass(d: number): string {
  if (d > 0) return 'text-success';
  if (d < 0) return 'text-danger';
  return 'text-text-muted';
}

function deltaSymbol(d: number): string {
  if (d > 0) return `↑ +${d}`;
  if (d < 0) return `↓ ${d}`;
  return '—';
}

function relativeTime(iso: string | null): string {
  if (iso === null) return '—';
  const diffMs = Date.now() - new Date(iso).getTime();
  const minutes = Math.floor(diffMs / 60000);
  if (minutes < 1) return 'à l\'instant';
  if (minutes < 60) return `il y a ${minutes} min`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `il y a ${hours} h`;
  const days = Math.floor(hours / 24);
  return `il y a ${days} j`;
}

function open(slug: string): void {
  router.push({ name: 'project-detail', params: { slug } });
}
</script>

<template>
  <div class="rounded-xl border border-border bg-surface p-5">
    <h3 class="mb-1 text-base font-semibold text-text">Récemment synchronisés</h3>
    <p class="mb-4 text-xs text-text-muted">5 derniers projets</p>
    <div class="flex flex-col gap-3">
      <button
        v-for="project in projects"
        :key="project.projectId"
        data-testid="recent-project"
        type="button"
        class="rounded-lg border border-border bg-background p-3 text-left transition hover:border-primary"
        @click="open(project.slug)"
      >
        <div class="mb-2 flex items-start justify-between">
          <div>
            <div class="text-sm font-semibold text-text">{{ project.name }}</div>
            <div class="font-mono text-xs text-text-muted">{{ project.slug }}</div>
          </div>
          <div class="text-xs text-text-muted">{{ relativeTime(project.lastSyncedAt) }}</div>
        </div>
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-border">
              <div
                data-testid="ratio-fill"
                class="h-full"
                :class="colorForRatio(project.upToDateRatio)"
                :style="{ width: `${project.upToDateRatio}%` }"
              />
            </div>
            <span class="text-xs font-semibold text-text">{{ project.upToDateRatio.toFixed(0) }}%</span>
          </div>
          <span data-testid="recent-delta" class="text-sm font-bold" :class="deltaClass(project.deltaSinceLastSync)">
            {{ deltaSymbol(project.deltaSinceLastSync) }}
          </span>
        </div>
      </button>
    </div>
  </div>
</template>
```

- [ ] **Step 4: Run test, verify pass**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm vitest run tests/unit/activity/components/DashboardRecentProjectsList.test.ts
```

Expected: PASS (7 tests).

- [ ] **Step 5: Lint + commit**

```
make lint-frontend
git add frontend/src/activity/components/DashboardRecentProjectsList.vue frontend/tests/unit/activity/components/DashboardRecentProjectsList.test.ts
git commit -m "feat(dashboard): add DashboardRecentProjectsList component"
```

---

## Task 21: Refactor `DashboardPage`

**Files:**
- Modify: `frontend/src/activity/pages/DashboardPage.vue`

- [ ] **Step 1: Replace the file**

```vue
<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

import DashboardEvolutionChart from '@/activity/components/DashboardEvolutionChart.vue';
import DashboardKpiCard from '@/activity/components/DashboardKpiCard.vue';
import DashboardRecentProjectsList from '@/activity/components/DashboardRecentProjectsList.vue';
import DashboardTotalSparkline from '@/activity/components/DashboardTotalSparkline.vue';
import { useDashboardStore } from '@/activity/stores/dashboard';
import type { DashboardRange } from '@/activity/types/dashboard.types';
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue';

const { t } = useI18n();
const store = useDashboardStore();

onMounted(() => {
  store.load();
});

const loading = computed(() => store.loading);
const metrics = computed(() => store.metrics);
const history = computed(() => store.history);
const recentProjects = computed(() => store.recentProjects);
const range = computed(() => store.range);

function labelFor(metricLabel: string): string {
  switch (metricLabel) {
    case 'total':
      return t('activity.dashboard.kpi.total');
    case 'upToDate':
      return t('activity.dashboard.kpi.upToDate');
    case 'outdated':
      return t('activity.dashboard.kpi.outdated');
    case 'vulnerabilities':
      return t('activity.dashboard.kpi.vulnerabilities');
    default:
      return metricLabel;
  }
}

function isVulnerabilities(label: string): boolean {
  return label === 'vulnerabilities';
}

function onRangeChange(r: DashboardRange): void {
  store.setRange(r);
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="dashboard-page">
      <h2 class="mb-6 text-2xl font-bold text-text" data-testid="dashboard-title">
        {{ t('activity.dashboard.welcome') }}
      </h2>

      <div v-if="loading" class="flex items-center justify-center py-12" data-testid="dashboard-loading">
        <span class="text-text-muted">{{ t('common.actions.loading') }}</span>
      </div>

      <div v-else>
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" data-testid="kpi-grid">
          <DashboardKpiCard
            v-for="metric in metrics"
            :key="metric.label"
            :label="labelFor(metric.label)"
            :value="metric.value"
            :delta="metric.change"
            :delta-inverted="isVulnerabilities(metric.label)"
          />
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
          <div class="space-y-4 lg:col-span-2">
            <DashboardEvolutionChart :points="history" :range="range" @update:range="onRangeChange" />
            <DashboardTotalSparkline :points="history" />
          </div>
          <div>
            <DashboardRecentProjectsList :projects="recentProjects" />
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
```

- [ ] **Step 2: Add translation keys**

Open `frontend/src/locales/fr.json`. Find the `activity.dashboard` block (or create it under `activity`). Add:

```json
{
  "activity": {
    "dashboard": {
      "welcome": "Tableau de bord",
      "kpi": {
        "total": "Total dépendances",
        "upToDate": "À jour",
        "outdated": "Obsolètes",
        "vulnerabilities": "Vulnérabilités"
      }
    }
  }
}
```

Merge it into the existing structure — do not overwrite unrelated keys. Repeat for `frontend/src/locales/en.json`:

```json
{
  "activity": {
    "dashboard": {
      "welcome": "Dashboard",
      "kpi": {
        "total": "Total dependencies",
        "upToDate": "Up to date",
        "outdated": "Outdated",
        "vulnerabilities": "Vulnerabilities"
      }
    }
  }
}
```

If `activity.dashboard.welcome` already exists, leave it as-is and only add the missing `kpi` subkeys.

- [ ] **Step 3: Run frontend tests + lint**

```
make lint-frontend && make test-frontend
```

Expected: 0 errors, 0 failures.

- [ ] **Step 4: Commit**

```
git add frontend/src/activity/pages/DashboardPage.vue frontend/src/locales/fr.json frontend/src/locales/en.json
git commit -m "feat(dashboard): wire DashboardPage to new components and store"
```

---

## Task 22: End-to-end manual smoke + final CI

- [ ] **Step 1: Trigger a full backend rebuild + restart**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend php bin/console cache:clear --env=prod
docker compose -f docker/compose.yaml -f docker/compose.override.yaml restart messenger-consumer
```

- [ ] **Step 2: Trigger a global sync via the UI or API**

Open the app at `http://monark.localhost`, log in, click the "Sync all" button (or `curl -X POST` on the sync endpoint with auth). Wait for the global sync job to complete.

- [ ] **Step 3: Verify a snapshot was inserted**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T database psql -U app -d monark -c "SELECT id, total_count, up_to_date_count, outdated_count, vulnerability_count, created_at FROM activity_dependency_stats_snapshots ORDER BY created_at DESC LIMIT 5;"
```

Expected: at least one row with non-zero counts.

- [ ] **Step 4: Verify per-project snapshots were inserted**

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T database psql -U app -d monark -c "SELECT project_id, total_count, up_to_date_count, created_at FROM activity_project_dependency_stats_snapshots ORDER BY created_at DESC LIMIT 10;"
```

Expected: one row per scanned project.

- [ ] **Step 5: Hit the dashboard endpoint**

```
TOKEN=$(docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend php bin/console app:auth:test-token 2>/dev/null || echo "")
curl -sH "Authorization: Bearer $TOKEN" "http://api.monark.localhost/api/v1/activity/dashboard?range=30d" | jq .
```

If there is no test-token command, log in via the UI and grab the JWT from the network tab. Verify the JSON response has `metrics` (4 items), `history` (≥1 item) and `recentProjects` (up to 5).

- [ ] **Step 6: Open the dashboard in the browser**

Visit `http://monark.localhost/dashboard` (or whatever the route is — check `frontend/src/router/index.ts` if unsure). Verify visually:

- 4 KPI cards with correct labels and values
- Evolution chart renders (or "Pas assez de données" if only 1 snapshot)
- Sparkline renders
- Recent projects list shows up to 5 projects with delta and ratio bar

Switch the range buttons and confirm the chart updates (refetches via `setRange`).

- [ ] **Step 7: Run the final full CI suite**

```
make ci
```

Expected: dashboard 100% green — backend lint, backend tests, frontend lint, frontend tests all pass.

- [ ] **Step 8: Commit any final touch-ups (if needed)**

If any test was missed or any lint issue surfaces, fix it in a focused commit.

---

## Self-review checklist

After writing this plan, this section was used to verify:

1. **Spec coverage:**
   - [x] Snapshot entities (Tasks 1, 2)
   - [x] Repositories with `findLatest`, `findPrevious`, `findInRange` and batch variants (Tasks 3, 5, 6)
   - [x] Migration creating both tables (Task 4)
   - [x] Snapshot capture on global sync completion (Tasks 8, 9)
   - [x] Snapshot capture on per-project scan (Task 10)
   - [x] DTOs and query/handler refactor (Tasks 11, 12, 13)
   - [x] Range query parameter end-to-end (Tasks 12, 14)
   - [x] Downsampling for 90d/1y (Task 13, `downsampleByDay`)
   - [x] Recent projects with delta calculation (Tasks 7, 13)
   - [x] Frontend layout (Two-column, Tasks 18, 21)
   - [x] All 4 components with the responsibilities described in the spec (Tasks 17–20)
   - [x] Hand-rolled SVG, no chart library (Tasks 18, 19)
   - [x] Vitest coverage matching the spec test table (Tasks 16, 17, 18, 19, 20)
   - [x] Pest coverage for domain, application listeners, application query, infrastructure repos, functional endpoint (Tasks 1, 2, 5, 6, 9, 10, 13, 14)

2. **Placeholder scan:** No `TBD`, `TODO`, or "fill in details" remain. Every code block contains complete, runnable code.

3. **Type consistency:**
   - `DashboardSnapshotPoint`, `DashboardRecentProject`, `DashboardOutput`, `DashboardMetric` are consistent across backend DTO and frontend types.
   - `DependencyStatsSnapshotRepositoryInterface` methods (`save`, `findLatest`, `findPrevious`, `findInRange`) are referenced identically in interface, repo impl, listener, query handler, and tests.
   - `ProjectDependencyStatsSnapshotRepositoryInterface` methods (`save`, `findLatestForProject`, `findPreviousForProject`, `findLatestForProjects`, `findPreviousForProjects`) are consistent across all tasks.
   - `GetDashboardQuery::VALID_RANGES` is used by the controller (Task 14) and matches the values in the handler's `match` (Task 13).
