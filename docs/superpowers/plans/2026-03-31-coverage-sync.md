# Coverage Sync Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Collect test coverage from CI pipelines (GitLab/GitHub) during global sync and display it on a dedicated dashboard page.

**Architecture:** New `Coverage` bounded context with `CoverageSnapshot` entity. Strategy pattern for CI providers (GitLab/GitHub). New Step 2 `SyncCoverage` inserted into global sync flow between SyncProjects and SyncVersions. Event-driven progress tracking via Mercure, identical pattern to existing steps.

**Tech Stack:** Symfony 8 (PHP 8.4), Doctrine ORM, Symfony Messenger (RabbitMQ), Symfony HttpClient, Mercure, Vue 3.5, Pinia, TailwindCSS, Pest 4, Vitest.

**Spec:** `docs/superpowers/specs/2026-03-31-coverage-sync-design.md`

---

## File Structure

### Backend — New files

```
backend/src/Coverage/
  Domain/
    Model/CoverageSnapshot.php          — Entity, table coverage_snapshots
    Model/CoverageSource.php            — Enum: ci_gitlab, ci_github, local_docker
    ValueObject/CoverageResult.php      — VO returned by providers
    Port/CoverageProviderInterface.php  — Strategy interface
    Repository/CoverageSnapshotRepositoryInterface.php
  Application/
    Command/FetchProjectCoverageCommand.php
    CommandHandler/FetchProjectCoverageHandler.php
    Query/GetCoverageDashboardQuery.php
    QueryHandler/GetCoverageDashboardQueryHandler.php
    Query/GetProjectCoverageHistoryQuery.php
    QueryHandler/GetProjectCoverageHistoryQueryHandler.php
    DTO/CoverageDashboardOutput.php
    DTO/CoverageProjectOutput.php
    DTO/CoverageSummaryOutput.php
  Infrastructure/
    Persistence/Doctrine/DoctrineCoverageSnapshotRepository.php
    Provider/CoverageProviderRegistry.php
    Provider/GitLabCoverageProvider.php
    Provider/GitHubCoverageProvider.php
  Presentation/
    Controller/GetCoverageDashboardController.php
    Controller/GetProjectCoverageHistoryController.php

backend/src/Shared/Domain/Event/ProjectCoverageFetchedEvent.php
backend/src/Sync/Application/EventListener/GlobalSyncCoverageProgressListener.php
```

### Backend — Modified files

```
backend/src/Sync/Domain/Model/GlobalSyncStep.php                          — Add SyncCoverage, reorder
backend/src/Sync/Application/EventListener/GlobalSyncProgressListener.php  — Transition to SyncCoverage
backend/src/Sync/Application/EventListener/GlobalSyncVersionProgressListener.php — Step 2→3, 3→4
backend/config/packages/messenger.yaml                                     — Route new command
backend/config/packages/doctrine.yaml                                      — Add Coverage mapping
```

### Backend — Test files

```
backend/tests/Unit/Coverage/Domain/Model/CoverageSnapshotTest.php
backend/tests/Unit/Coverage/Domain/Model/CoverageSourceTest.php
backend/tests/Unit/Coverage/Infrastructure/Provider/GitLabCoverageProviderTest.php
backend/tests/Unit/Coverage/Infrastructure/Provider/GitHubCoverageProviderTest.php
backend/tests/Unit/Coverage/Infrastructure/Provider/CoverageProviderRegistryTest.php
backend/tests/Unit/Coverage/Application/CommandHandler/FetchProjectCoverageHandlerTest.php
backend/tests/Unit/Sync/Domain/Model/GlobalSyncStepTest.php               — Update existing
backend/tests/Unit/Sync/Domain/Model/GlobalSyncJobTest.php                — Update existing
backend/tests/Unit/Sync/Application/EventListener/GlobalSyncCoverageProgressListenerTest.php
```

### Frontend — New files

```
frontend/src/coverage/types/index.ts
frontend/src/coverage/services/coverage.service.ts
frontend/src/coverage/stores/coverage.ts
frontend/src/coverage/pages/CoverageDashboard.vue
frontend/src/coverage/components/CoverageSummaryCard.vue
frontend/src/coverage/components/CoverageProjectList.vue
frontend/src/coverage/routes.ts
frontend/tests/coverage/pages/CoverageDashboard.test.ts
frontend/tests/coverage/components/CoverageSummaryCard.test.ts
frontend/tests/coverage/components/CoverageProjectList.test.ts
```

### Frontend — Modified files

```
frontend/src/shared/types/globalSync.ts      — Add sync_coverage step
frontend/src/shared/components/SyncProgressBanner.vue — 4 breadcrumbs
frontend/src/shared/components/SyncButton.vue — Step x/4
frontend/src/app/router.ts                    — Import coverage routes
frontend/src/shared/layouts/DashboardLayout.vue — Add nav link (if sidebar)
```

---

## Task 1: Coverage Domain Model

**Files:**
- Create: `backend/src/Coverage/Domain/Model/CoverageSource.php`
- Create: `backend/src/Coverage/Domain/Model/CoverageSnapshot.php`
- Create: `backend/src/Coverage/Domain/ValueObject/CoverageResult.php`
- Test: `backend/tests/Unit/Coverage/Domain/Model/CoverageSourceTest.php`
- Test: `backend/tests/Unit/Coverage/Domain/Model/CoverageSnapshotTest.php`

- [ ] **Step 1: Write CoverageSource enum test**

```php
<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\ProviderType;
use App\Coverage\Domain\Model\CoverageSource;

describe('CoverageSource', function (): void {
    it('has three cases', function (): void {
        expect(CoverageSource::cases())->toHaveCount(3);
    });

    it('maps from GitLab provider type', function (): void {
        expect(CoverageSource::fromProviderType(ProviderType::GitLab))
            ->toBe(CoverageSource::CiGitlab);
    });

    it('maps from GitHub provider type', function (): void {
        expect(CoverageSource::fromProviderType(ProviderType::GitHub))
            ->toBe(CoverageSource::CiGithub);
    });

    it('throws for Bitbucket provider type', function (): void {
        CoverageSource::fromProviderType(ProviderType::Bitbucket);
    })->throws(\LogicException::class, 'Bitbucket coverage not supported yet.');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Coverage/Domain/Model/CoverageSourceTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Implement CoverageSource enum**

```php
<?php

declare(strict_types=1);

namespace App\Coverage\Domain\Model;

use App\Catalog\Domain\Model\ProviderType;

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

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Coverage/Domain/Model/CoverageSourceTest.php`
Expected: PASS

- [ ] **Step 5: Write CoverageSnapshot test**

```php
<?php

declare(strict_types=1);

use App\Coverage\Domain\Model\CoverageSnapshot;
use App\Coverage\Domain\Model\CoverageSource;
use Symfony\Component\Uid\Uuid;

describe('CoverageSnapshot', function (): void {
    it('creates with valid data', function (): void {
        $projectId = Uuid::v7();
        $snapshot = CoverageSnapshot::create(
            projectId: $projectId,
            commitHash: 'a3f21bc4e5d6f7890123456789abcdef01234567',
            coveragePercent: 82.3,
            source: CoverageSource::CiGitlab,
            ref: 'main',
            pipelineId: '12345',
        );

        expect($snapshot->getId())->toBeInstanceOf(Uuid::class)
            ->and($snapshot->getProjectId())->toBe($projectId)
            ->and($snapshot->getCommitHash())->toBe('a3f21bc4e5d6f7890123456789abcdef01234567')
            ->and($snapshot->getCoveragePercent())->toBe(82.3)
            ->and($snapshot->getSource())->toBe(CoverageSource::CiGitlab)
            ->and($snapshot->getRef())->toBe('main')
            ->and($snapshot->getPipelineId())->toBe('12345')
            ->and($snapshot->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('creates without pipeline id', function (): void {
        $snapshot = CoverageSnapshot::create(
            projectId: Uuid::v7(),
            commitHash: 'a3f21bc4e5d6f7890123456789abcdef01234567',
            coveragePercent: 64.7,
            source: CoverageSource::CiGithub,
            ref: 'main',
        );

        expect($snapshot->getPipelineId())->toBeNull();
    });

    it('rejects negative coverage', function (): void {
        CoverageSnapshot::create(
            projectId: Uuid::v7(),
            commitHash: 'a3f21bc4e5d6f7890123456789abcdef01234567',
            coveragePercent: -1.0,
            source: CoverageSource::CiGitlab,
            ref: 'main',
        );
    })->throws(\InvalidArgumentException::class);

    it('rejects coverage above 100', function (): void {
        CoverageSnapshot::create(
            projectId: Uuid::v7(),
            commitHash: 'a3f21bc4e5d6f7890123456789abcdef01234567',
            coveragePercent: 100.1,
            source: CoverageSource::CiGitlab,
            ref: 'main',
        );
    })->throws(\InvalidArgumentException::class);

    it('rejects empty commit hash', function (): void {
        CoverageSnapshot::create(
            projectId: Uuid::v7(),
            commitHash: '',
            coveragePercent: 80.0,
            source: CoverageSource::CiGitlab,
            ref: 'main',
        );
    })->throws(\InvalidArgumentException::class);
});
```

- [ ] **Step 6: Run test to verify it fails**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Coverage/Domain/Model/CoverageSnapshotTest.php`
Expected: FAIL — class not found

- [ ] **Step 7: Implement CoverageSnapshot entity**

```php
<?php

declare(strict_types=1);

namespace App\Coverage\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'coverage_snapshots')]
#[ORM\Index(name: 'idx_coverage_project', columns: ['project_id'])]
#[ORM\Index(name: 'idx_coverage_project_commit', columns: ['project_id', 'commit_hash'])]
final class CoverageSnapshot
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'uuid')]
    private Uuid $projectId;

    #[ORM\Column(length: 40)]
    private string $commitHash;

    #[ORM\Column(type: 'float')]
    private float $coveragePercent;

    #[ORM\Column(type: 'string', enumType: CoverageSource::class)]
    private CoverageSource $source;

    #[ORM\Column(length: 255)]
    private string $ref;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pipelineId;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    private function __construct(
        Uuid $id,
        Uuid $projectId,
        string $commitHash,
        float $coveragePercent,
        CoverageSource $source,
        string $ref,
        ?string $pipelineId,
    ) {
        if (\trim($commitHash) === '') {
            throw new InvalidArgumentException('Commit hash must not be blank.');
        }
        if ($coveragePercent < 0.0 || $coveragePercent > 100.0) {
            throw new InvalidArgumentException('Coverage percent must be between 0 and 100.');
        }

        $this->id = $id;
        $this->projectId = $projectId;
        $this->commitHash = $commitHash;
        $this->coveragePercent = $coveragePercent;
        $this->source = $source;
        $this->ref = $ref;
        $this->pipelineId = $pipelineId;
        $this->createdAt = new DateTimeImmutable();
    }

    public static function create(
        Uuid $projectId,
        string $commitHash,
        float $coveragePercent,
        CoverageSource $source,
        string $ref,
        ?string $pipelineId = null,
    ): self {
        return new self(
            id: Uuid::v7(),
            projectId: $projectId,
            commitHash: $commitHash,
            coveragePercent: $coveragePercent,
            source: $source,
            ref: $ref,
            pipelineId: $pipelineId,
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

    public function getCommitHash(): string
    {
        return $this->commitHash;
    }

    public function getCoveragePercent(): float
    {
        return $this->coveragePercent;
    }

    public function getSource(): CoverageSource
    {
        return $this->source;
    }

    public function getRef(): string
    {
        return $this->ref;
    }

    public function getPipelineId(): ?string
    {
        return $this->pipelineId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
```

- [ ] **Step 8: Implement CoverageResult value object**

```php
<?php

declare(strict_types=1);

namespace App\Coverage\Domain\ValueObject;

final readonly class CoverageResult
{
    public function __construct(
        public float $coveragePercent,
        public string $commitHash,
        public string $ref,
        public ?string $pipelineId,
    ) {
    }
}
```

- [ ] **Step 9: Run all Coverage domain tests**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Coverage/Domain`
Expected: PASS

- [ ] **Step 10: Commit**

```bash
git add backend/src/Coverage/Domain/ backend/tests/Unit/Coverage/Domain/
git commit -m "feat(coverage): add domain model — CoverageSnapshot, CoverageSource, CoverageResult"
```

---

## Task 2: Coverage Repository + Migration

**Files:**
- Create: `backend/src/Coverage/Domain/Repository/CoverageSnapshotRepositoryInterface.php`
- Create: `backend/src/Coverage/Infrastructure/Persistence/Doctrine/DoctrineCoverageSnapshotRepository.php`
- Modify: `backend/config/packages/doctrine.yaml` — add Coverage mapping

- [ ] **Step 1: Create repository interface**

```php
<?php

declare(strict_types=1);

namespace App\Coverage\Domain\Repository;

use App\Coverage\Domain\Model\CoverageSnapshot;
use Symfony\Component\Uid\Uuid;

interface CoverageSnapshotRepositoryInterface
{
    public function save(CoverageSnapshot $snapshot): void;

    public function findLatestByProject(Uuid $projectId): ?CoverageSnapshot;

    /** @return list<CoverageSnapshot> */
    public function findAllByProject(Uuid $projectId, int $limit = 50): array;

    /** @return list<CoverageSnapshot> Returns one snapshot per project (the latest) */
    public function findLatestPerProject(): array;

    /** @return list<CoverageSnapshot> Returns the second-latest per project (for trend) */
    public function findPreviousPerProject(): array;
}
```

- [ ] **Step 2: Implement Doctrine repository**

```php
<?php

declare(strict_types=1);

namespace App\Coverage\Infrastructure\Persistence\Doctrine;

use App\Coverage\Domain\Model\CoverageSnapshot;
use App\Coverage\Domain\Repository\CoverageSnapshotRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineCoverageSnapshotRepository implements CoverageSnapshotRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function save(CoverageSnapshot $snapshot): void
    {
        $this->em->persist($snapshot);
        $this->em->flush();
    }

    public function findLatestByProject(Uuid $projectId): ?CoverageSnapshot
    {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(CoverageSnapshot::class, 's')
            ->where('s.projectId = :projectId')
            ->setParameter('projectId', $projectId, 'uuid')
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<CoverageSnapshot> */
    public function findAllByProject(Uuid $projectId, int $limit = 50): array
    {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(CoverageSnapshot::class, 's')
            ->where('s.projectId = :projectId')
            ->setParameter('projectId', $projectId, 'uuid')
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return list<CoverageSnapshot> */
    public function findLatestPerProject(): array
    {
        $subquery = $this->em->createQueryBuilder()
            ->select('MAX(sub.id)')
            ->from(CoverageSnapshot::class, 'sub')
            ->groupBy('sub.projectId')
            ->getDQL();

        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(CoverageSnapshot::class, 's')
            ->where("s.id IN ({$subquery})")
            ->orderBy('s.coveragePercent', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<CoverageSnapshot> */
    public function findPreviousPerProject(): array
    {
        $conn = $this->em->getConnection();
        $sql = <<<'SQL'
            SELECT cs.id
            FROM coverage_snapshots cs
            INNER JOIN (
                SELECT project_id, MAX(created_at) AS max_created
                FROM coverage_snapshots
                WHERE (project_id, created_at) NOT IN (
                    SELECT project_id, MAX(created_at)
                    FROM coverage_snapshots
                    GROUP BY project_id
                )
                GROUP BY project_id
            ) prev ON cs.project_id = prev.project_id AND cs.created_at = prev.max_created
            SQL;

        $ids = $conn->fetchFirstColumn($sql);
        if ($ids === []) {
            return [];
        }

        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(CoverageSnapshot::class, 's')
            ->where('s.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }
}
```

- [ ] **Step 3: Add Doctrine ORM mapping for Coverage context**

Check `backend/config/packages/doctrine.yaml` for existing mappings and add the Coverage mapping following the same pattern as other bounded contexts (Catalog, Dependency, Sync). Add under `doctrine.orm.mappings`:

```yaml
Coverage:
    type: attribute
    is_bundle: false
    dir: '%kernel.project_dir%/src/Coverage/Domain/Model'
    prefix: App\Coverage\Domain\Model
    alias: Coverage
```

- [ ] **Step 4: Generate and review migration**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend php bin/console doctrine:migrations:diff`

Verify the generated migration creates `coverage_snapshots` table with:
- `id` UUID PK
- `project_id` UUID NOT NULL with FK to `catalog_projects(id)` ON DELETE CASCADE
- `commit_hash` VARCHAR(40) NOT NULL
- `coverage_percent` DOUBLE PRECISION NOT NULL
- `source` VARCHAR(255) NOT NULL
- `ref` VARCHAR(255) NOT NULL
- `pipeline_id` VARCHAR(255) nullable
- `created_at` TIMESTAMP NOT NULL
- Indexes: `idx_coverage_project`, `idx_coverage_project_commit`

- [ ] **Step 5: Run migration**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend php bin/console doctrine:migrations:migrate --no-interaction`
Expected: Migration applied successfully

- [ ] **Step 6: Commit**

```bash
git add backend/src/Coverage/Domain/Repository/ backend/src/Coverage/Infrastructure/Persistence/ backend/config/packages/doctrine.yaml backend/migrations/
git commit -m "feat(coverage): add repository and migration"
```

---

## Task 3: GlobalSyncStep Reordering

**Files:**
- Modify: `backend/src/Sync/Domain/Model/GlobalSyncStep.php`
- Modify: `backend/tests/Unit/Sync/Domain/Model/GlobalSyncStepTest.php` (if exists)
- Modify: `backend/tests/Unit/Sync/Domain/Model/GlobalSyncJobTest.php` (if exists)

- [ ] **Step 1: Write/update GlobalSyncStep test**

```php
<?php

declare(strict_types=1);

use App\Sync\Domain\Model\GlobalSyncStep;

describe('GlobalSyncStep', function (): void {
    it('has four cases', function (): void {
        expect(GlobalSyncStep::cases())->toHaveCount(4);
    });

    it('orders steps correctly', function (): void {
        expect(GlobalSyncStep::SyncProjects->value)->toBe(1)
            ->and(GlobalSyncStep::SyncCoverage->value)->toBe(2)
            ->and(GlobalSyncStep::SyncVersions->value)->toBe(3)
            ->and(GlobalSyncStep::ScanCve->value)->toBe(4);
    });

    it('returns correct step names', function (): void {
        expect(GlobalSyncStep::SyncProjects->name())->toBe('sync_projects')
            ->and(GlobalSyncStep::SyncCoverage->name())->toBe('sync_coverage')
            ->and(GlobalSyncStep::SyncVersions->name())->toBe('sync_versions')
            ->and(GlobalSyncStep::ScanCve->name())->toBe('scan_cve');
    });

    it('chains next steps correctly', function (): void {
        expect(GlobalSyncStep::SyncProjects->next())->toBe(GlobalSyncStep::SyncCoverage)
            ->and(GlobalSyncStep::SyncCoverage->next())->toBe(GlobalSyncStep::SyncVersions)
            ->and(GlobalSyncStep::SyncVersions->next())->toBe(GlobalSyncStep::ScanCve)
            ->and(GlobalSyncStep::ScanCve->next())->toBeNull();
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Sync/Domain/Model/GlobalSyncStepTest.php`
Expected: FAIL — SyncCoverage case does not exist

- [ ] **Step 3: Update GlobalSyncStep enum**

Replace the full content of `backend/src/Sync/Domain/Model/GlobalSyncStep.php`:

```php
<?php

declare(strict_types=1);

namespace App\Sync\Domain\Model;

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

- [ ] **Step 4: Update GlobalSyncJobTest to expect 4 steps**

Update any existing test that references step counts or step values (SyncVersions was 2, now 3; ScanCve was 3, now 4). Fix `getCompletedStepNames()` expectations to include `sync_coverage`.

- [ ] **Step 5: Run all Sync domain tests**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Sync/Domain`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add backend/src/Sync/Domain/Model/GlobalSyncStep.php backend/tests/Unit/Sync/Domain/
git commit -m "feat(sync): add SyncCoverage step — reorder to 1-2-3-4"
```

---

## Task 4: CoverageProvider Interface + Registry

**Files:**
- Create: `backend/src/Coverage/Domain/Port/CoverageProviderInterface.php`
- Create: `backend/src/Coverage/Infrastructure/Provider/CoverageProviderRegistry.php`
- Test: `backend/tests/Unit/Coverage/Infrastructure/Provider/CoverageProviderRegistryTest.php`

- [ ] **Step 1: Create CoverageProviderInterface**

```php
<?php

declare(strict_types=1);

namespace App\Coverage\Domain\Port;

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProviderType;
use App\Coverage\Domain\ValueObject\CoverageResult;

interface CoverageProviderInterface
{
    public function supports(ProviderType $type): bool;

    public function fetchCoverage(Project $project): ?CoverageResult;
}
```

- [ ] **Step 2: Write CoverageProviderRegistry test**

```php
<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\ProviderType;
use App\Coverage\Domain\Port\CoverageProviderInterface;
use App\Coverage\Infrastructure\Provider\CoverageProviderRegistry;

describe('CoverageProviderRegistry', function (): void {
    it('resolves a provider that supports the type', function (): void {
        $provider = Mockery::mock(CoverageProviderInterface::class);
        $provider->shouldReceive('supports')->with(ProviderType::GitLab)->andReturn(true);

        $registry = new CoverageProviderRegistry([$provider]);

        expect($registry->resolve(ProviderType::GitLab))->toBe($provider);
    });

    it('returns null when no provider supports the type', function (): void {
        $provider = Mockery::mock(CoverageProviderInterface::class);
        $provider->shouldReceive('supports')->with(ProviderType::Bitbucket)->andReturn(false);

        $registry = new CoverageProviderRegistry([$provider]);

        expect($registry->resolve(ProviderType::Bitbucket))->toBeNull();
    });

    it('returns null with empty providers', function (): void {
        $registry = new CoverageProviderRegistry([]);

        expect($registry->resolve(ProviderType::GitLab))->toBeNull();
    });
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Coverage/Infrastructure/Provider/CoverageProviderRegistryTest.php`
Expected: FAIL — class not found

- [ ] **Step 4: Implement CoverageProviderRegistry**

```php
<?php

declare(strict_types=1);

namespace App\Coverage\Infrastructure\Provider;

use App\Catalog\Domain\Model\ProviderType;
use App\Coverage\Domain\Port\CoverageProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class CoverageProviderRegistry
{
    /** @param iterable<CoverageProviderInterface> $providers */
    public function __construct(
        #[AutowireIterator('app.coverage_provider')]
        private iterable $providers,
    ) {
    }

    public function resolve(ProviderType $type): ?CoverageProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($type)) {
                return $provider;
            }
        }

        return null;
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Coverage/Infrastructure/Provider/CoverageProviderRegistryTest.php`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add backend/src/Coverage/Domain/Port/ backend/src/Coverage/Infrastructure/Provider/CoverageProviderRegistry.php backend/tests/Unit/Coverage/Infrastructure/Provider/
git commit -m "feat(coverage): add CoverageProviderInterface and registry"
```

---

## Task 5: GitLabCoverageProvider

**Files:**
- Create: `backend/src/Coverage/Infrastructure/Provider/GitLabCoverageProvider.php`
- Test: `backend/tests/Unit/Coverage/Infrastructure/Provider/GitLabCoverageProviderTest.php`

- [ ] **Step 1: Write GitLabCoverageProvider test**

```php
<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\ProviderType;
use App\Coverage\Infrastructure\Provider\GitLabCoverageProvider;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

describe('GitLabCoverageProvider', function (): void {
    it('supports GitLab type', function (): void {
        $http = Mockery::mock(HttpClientInterface::class);
        $provider = new GitLabCoverageProvider($http, new NullLogger());

        expect($provider->supports(ProviderType::GitLab))->toBeTrue()
            ->and($provider->supports(ProviderType::GitHub))->toBeFalse();
    });

    it('fetches coverage from latest successful pipeline', function (): void {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('toArray')->andReturn([
            [
                'id' => 12345,
                'sha' => 'a3f21bc4e5d6f7890123456789abcdef01234567',
                'ref' => 'main',
                'coverage' => '82.30',
            ],
        ]);

        $http = Mockery::mock(HttpClientInterface::class);
        $http->shouldReceive('request')
            ->with('GET', 'https://gitlab.example.com/api/v4/projects/42/pipelines', Mockery::on(function (array $options): bool {
                return $options['query']['ref'] === 'main'
                    && $options['query']['status'] === 'success'
                    && $options['query']['per_page'] === 1;
            }))
            ->andReturn($response);

        $gitlabProvider = new GitLabCoverageProvider($http, new NullLogger());

        $gitProvider = Mockery::mock(Provider::class);
        $gitProvider->shouldReceive('getUrl')->andReturn('https://gitlab.example.com');
        $gitProvider->shouldReceive('getApiToken')->andReturn('glpat-test');

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getExternalId')->andReturn('42');
        $project->shouldReceive('getDefaultBranch')->andReturn('main');
        $project->shouldReceive('getProvider')->andReturn($gitProvider);

        $result = $gitlabProvider->fetchCoverage($project);

        expect($result)->not->toBeNull()
            ->and($result->coveragePercent)->toBe(82.3)
            ->and($result->commitHash)->toBe('a3f21bc4e5d6f7890123456789abcdef01234567')
            ->and($result->ref)->toBe('main')
            ->and($result->pipelineId)->toBe('12345');
    });

    it('returns null when pipeline has no coverage', function (): void {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('toArray')->andReturn([
            ['id' => 12345, 'sha' => 'abc123', 'ref' => 'main', 'coverage' => null],
        ]);

        $http = Mockery::mock(HttpClientInterface::class);
        $http->shouldReceive('request')->andReturn($response);

        $gitlabProvider = new GitLabCoverageProvider($http, new NullLogger());

        $gitProvider = Mockery::mock(Provider::class);
        $gitProvider->shouldReceive('getUrl')->andReturn('https://gitlab.example.com');
        $gitProvider->shouldReceive('getApiToken')->andReturn('token');

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getExternalId')->andReturn('42');
        $project->shouldReceive('getDefaultBranch')->andReturn('main');
        $project->shouldReceive('getProvider')->andReturn($gitProvider);

        expect($gitlabProvider->fetchCoverage($project))->toBeNull();
    });

    it('returns null when no pipeline found', function (): void {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('toArray')->andReturn([]);

        $http = Mockery::mock(HttpClientInterface::class);
        $http->shouldReceive('request')->andReturn($response);

        $gitlabProvider = new GitLabCoverageProvider($http, new NullLogger());

        $gitProvider = Mockery::mock(Provider::class);
        $gitProvider->shouldReceive('getUrl')->andReturn('https://gitlab.example.com');
        $gitProvider->shouldReceive('getApiToken')->andReturn('token');

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getExternalId')->andReturn('42');
        $project->shouldReceive('getDefaultBranch')->andReturn('main');
        $project->shouldReceive('getProvider')->andReturn($gitProvider);

        expect($gitlabProvider->fetchCoverage($project))->toBeNull();
    });

    it('returns null on API error', function (): void {
        $http = Mockery::mock(HttpClientInterface::class);
        $http->shouldReceive('request')->andThrow(new \Exception('Connection refused'));

        $gitlabProvider = new GitLabCoverageProvider($http, new NullLogger());

        $gitProvider = Mockery::mock(Provider::class);
        $gitProvider->shouldReceive('getUrl')->andReturn('https://gitlab.example.com');
        $gitProvider->shouldReceive('getApiToken')->andReturn('token');

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getExternalId')->andReturn('42');
        $project->shouldReceive('getDefaultBranch')->andReturn('main');
        $project->shouldReceive('getProvider')->andReturn($gitProvider);
        $project->shouldReceive('getName')->andReturn('test-project');

        expect($gitlabProvider->fetchCoverage($project))->toBeNull();
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Coverage/Infrastructure/Provider/GitLabCoverageProviderTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Implement GitLabCoverageProvider**

```php
<?php

declare(strict_types=1);

namespace App\Coverage\Infrastructure\Provider;

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProviderType;
use App\Coverage\Domain\Port\CoverageProviderInterface;
use App\Coverage\Domain\ValueObject\CoverageResult;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AutoconfigureTag('app.coverage_provider')]
final readonly class GitLabCoverageProvider implements CoverageProviderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    public function supports(ProviderType $type): bool
    {
        return $type === ProviderType::GitLab;
    }

    public function fetchCoverage(Project $project): ?CoverageResult
    {
        $provider = $project->getProvider();
        $baseUrl = \rtrim($provider->getUrl(), '/');

        try {
            $response = $this->httpClient->request('GET', \sprintf(
                '%s/api/v4/projects/%s/pipelines',
                $baseUrl,
                $project->getExternalId(),
            ), [
                'headers' => ['PRIVATE-TOKEN' => $provider->getApiToken()],
                'query' => [
                    'ref' => $project->getDefaultBranch(),
                    'status' => 'success',
                    'per_page' => 1,
                ],
                'timeout' => 15,
            ]);

            $pipelines = $response->toArray();
            if ($pipelines === []) {
                return null;
            }

            $pipeline = $pipelines[0];
            $coverage = $pipeline['coverage'] ?? null;
            if ($coverage === null) {
                return null;
            }

            return new CoverageResult(
                coveragePercent: (float) $coverage,
                commitHash: $pipeline['sha'],
                ref: $pipeline['ref'],
                pipelineId: (string) $pipeline['id'],
            );
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to fetch GitLab coverage for project {project}: {error}', [
                'project' => $project->getName(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Coverage/Infrastructure/Provider/GitLabCoverageProviderTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add backend/src/Coverage/Infrastructure/Provider/GitLabCoverageProvider.php backend/tests/Unit/Coverage/Infrastructure/Provider/GitLabCoverageProviderTest.php
git commit -m "feat(coverage): add GitLab coverage provider"
```

---

## Task 6: GitHubCoverageProvider

**Files:**
- Create: `backend/src/Coverage/Infrastructure/Provider/GitHubCoverageProvider.php`
- Test: `backend/tests/Unit/Coverage/Infrastructure/Provider/GitHubCoverageProviderTest.php`

- [ ] **Step 1: Write GitHubCoverageProvider test**

```php
<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\ProviderType;
use App\Coverage\Infrastructure\Provider\GitHubCoverageProvider;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

describe('GitHubCoverageProvider', function (): void {
    it('supports GitHub type', function (): void {
        $http = Mockery::mock(HttpClientInterface::class);
        $provider = new GitHubCoverageProvider($http, new NullLogger());

        expect($provider->supports(ProviderType::GitHub))->toBeTrue()
            ->and($provider->supports(ProviderType::GitLab))->toBeFalse();
    });

    it('fetches coverage from check run output', function (): void {
        $runsResponse = Mockery::mock(ResponseInterface::class);
        $runsResponse->shouldReceive('getStatusCode')->andReturn(200);
        $runsResponse->shouldReceive('toArray')->andReturn([
            'workflow_runs' => [[
                'id' => 99,
                'head_sha' => 'f8e12d4abc123def456789012345678901234567',
                'head_branch' => 'main',
            ]],
        ]);

        $checksResponse = Mockery::mock(ResponseInterface::class);
        $checksResponse->shouldReceive('getStatusCode')->andReturn(200);
        $checksResponse->shouldReceive('toArray')->andReturn([
            'check_runs' => [[
                'output' => ['summary' => 'Total coverage: 64.7%'],
            ]],
        ]);

        $http = Mockery::mock(HttpClientInterface::class);
        $http->shouldReceive('request')
            ->with('GET', 'https://api.github.com/repos/owner/repo/actions/runs', Mockery::any())
            ->andReturn($runsResponse);
        $http->shouldReceive('request')
            ->with('GET', 'https://api.github.com/repos/owner/repo/check-runs', Mockery::any())
            ->andReturn($checksResponse);

        $githubProvider = new GitHubCoverageProvider($http, new NullLogger());

        $gitProvider = Mockery::mock(Provider::class);
        $gitProvider->shouldReceive('getUrl')->andReturn('https://api.github.com');
        $gitProvider->shouldReceive('getApiToken')->andReturn('ghp_test');

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getRepositoryUrl')->andReturn('https://github.com/owner/repo');
        $project->shouldReceive('getDefaultBranch')->andReturn('main');
        $project->shouldReceive('getProvider')->andReturn($gitProvider);

        $result = $githubProvider->fetchCoverage($project);

        expect($result)->not->toBeNull()
            ->and($result->coveragePercent)->toBe(64.7)
            ->and($result->commitHash)->toBe('f8e12d4abc123def456789012345678901234567')
            ->and($result->ref)->toBe('main')
            ->and($result->pipelineId)->toBe('99');
    });

    it('returns null when no coverage in check runs', function (): void {
        $runsResponse = Mockery::mock(ResponseInterface::class);
        $runsResponse->shouldReceive('getStatusCode')->andReturn(200);
        $runsResponse->shouldReceive('toArray')->andReturn([
            'workflow_runs' => [['id' => 99, 'head_sha' => 'abc', 'head_branch' => 'main']],
        ]);

        $checksResponse = Mockery::mock(ResponseInterface::class);
        $checksResponse->shouldReceive('getStatusCode')->andReturn(200);
        $checksResponse->shouldReceive('toArray')->andReturn([
            'check_runs' => [['output' => ['summary' => 'All tests passed']]],
        ]);

        $http = Mockery::mock(HttpClientInterface::class);
        $http->shouldReceive('request')->andReturn($runsResponse, $checksResponse);

        $githubProvider = new GitHubCoverageProvider($http, new NullLogger());

        $gitProvider = Mockery::mock(Provider::class);
        $gitProvider->shouldReceive('getUrl')->andReturn('https://api.github.com');
        $gitProvider->shouldReceive('getApiToken')->andReturn('ghp_test');

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getRepositoryUrl')->andReturn('https://github.com/owner/repo');
        $project->shouldReceive('getDefaultBranch')->andReturn('main');
        $project->shouldReceive('getProvider')->andReturn($gitProvider);

        expect($githubProvider->fetchCoverage($project))->toBeNull();
    });

    it('returns null when no workflow runs found', function (): void {
        $runsResponse = Mockery::mock(ResponseInterface::class);
        $runsResponse->shouldReceive('getStatusCode')->andReturn(200);
        $runsResponse->shouldReceive('toArray')->andReturn(['workflow_runs' => []]);

        $http = Mockery::mock(HttpClientInterface::class);
        $http->shouldReceive('request')->andReturn($runsResponse);

        $githubProvider = new GitHubCoverageProvider($http, new NullLogger());

        $gitProvider = Mockery::mock(Provider::class);
        $gitProvider->shouldReceive('getUrl')->andReturn('https://api.github.com');
        $gitProvider->shouldReceive('getApiToken')->andReturn('ghp_test');

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getRepositoryUrl')->andReturn('https://github.com/owner/repo');
        $project->shouldReceive('getDefaultBranch')->andReturn('main');
        $project->shouldReceive('getProvider')->andReturn($gitProvider);

        expect($githubProvider->fetchCoverage($project))->toBeNull();
    });

    it('extracts owner/repo from repository URL', function (): void {
        $runsResponse = Mockery::mock(ResponseInterface::class);
        $runsResponse->shouldReceive('getStatusCode')->andReturn(200);
        $runsResponse->shouldReceive('toArray')->andReturn(['workflow_runs' => []]);

        $http = Mockery::mock(HttpClientInterface::class);
        $http->shouldReceive('request')
            ->with('GET', 'https://api.github.com/repos/my-org/my-repo/actions/runs', Mockery::any())
            ->andReturn($runsResponse);

        $githubProvider = new GitHubCoverageProvider($http, new NullLogger());

        $gitProvider = Mockery::mock(Provider::class);
        $gitProvider->shouldReceive('getUrl')->andReturn('https://api.github.com');
        $gitProvider->shouldReceive('getApiToken')->andReturn('ghp_test');

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getRepositoryUrl')->andReturn('https://github.com/my-org/my-repo.git');
        $project->shouldReceive('getDefaultBranch')->andReturn('main');
        $project->shouldReceive('getProvider')->andReturn($gitProvider);

        $githubProvider->fetchCoverage($project);
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Coverage/Infrastructure/Provider/GitHubCoverageProviderTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Implement GitHubCoverageProvider**

```php
<?php

declare(strict_types=1);

namespace App\Coverage\Infrastructure\Provider;

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProviderType;
use App\Coverage\Domain\Port\CoverageProviderInterface;
use App\Coverage\Domain\ValueObject\CoverageResult;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AutoconfigureTag('app.coverage_provider')]
final readonly class GitHubCoverageProvider implements CoverageProviderInterface
{
    private const COVERAGE_PATTERN = '/coverage[:\s]+(\d+\.?\d*)%/i';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    public function supports(ProviderType $type): bool
    {
        return $type === ProviderType::GitHub;
    }

    public function fetchCoverage(Project $project): ?CoverageResult
    {
        $provider = $project->getProvider();
        $baseUrl = \rtrim($provider->getUrl(), '/');
        $ownerRepo = $this->extractOwnerRepo($project->getRepositoryUrl());

        try {
            $runsResponse = $this->httpClient->request('GET', \sprintf(
                '%s/repos/%s/actions/runs',
                $baseUrl,
                $ownerRepo,
            ), [
                'headers' => ['Authorization' => \sprintf('Bearer %s', $provider->getApiToken())],
                'query' => [
                    'branch' => $project->getDefaultBranch(),
                    'status' => 'success',
                    'per_page' => 1,
                ],
                'timeout' => 15,
            ]);

            $runs = $runsResponse->toArray()['workflow_runs'] ?? [];
            if ($runs === []) {
                return null;
            }

            $run = $runs[0];
            $sha = $run['head_sha'];
            $ref = $run['head_branch'];

            $checksResponse = $this->httpClient->request('GET', \sprintf(
                '%s/repos/%s/check-runs',
                $baseUrl,
                $ownerRepo,
            ), [
                'headers' => ['Authorization' => \sprintf('Bearer %s', $provider->getApiToken())],
                'query' => ['head_sha' => $sha],
                'timeout' => 15,
            ]);

            $checkRuns = $checksResponse->toArray()['check_runs'] ?? [];
            foreach ($checkRuns as $checkRun) {
                $summary = $checkRun['output']['summary'] ?? '';
                if (\preg_match(self::COVERAGE_PATTERN, $summary, $matches)) {
                    return new CoverageResult(
                        coveragePercent: (float) $matches[1],
                        commitHash: $sha,
                        ref: $ref,
                        pipelineId: (string) $run['id'],
                    );
                }
            }

            return null;
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to fetch GitHub coverage for project {project}: {error}', [
                'project' => $project->getName(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function extractOwnerRepo(string $repositoryUrl): string
    {
        $path = \parse_url($repositoryUrl, \PHP_URL_PATH) ?? '';
        $path = \trim($path, '/');
        $path = \preg_replace('/\.git$/', '', $path);

        return $path;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Coverage/Infrastructure/Provider/GitHubCoverageProviderTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add backend/src/Coverage/Infrastructure/Provider/GitHubCoverageProvider.php backend/tests/Unit/Coverage/Infrastructure/Provider/GitHubCoverageProviderTest.php
git commit -m "feat(coverage): add GitHub coverage provider"
```

---

## Task 7: FetchProjectCoverageCommand + Handler

**Files:**
- Create: `backend/src/Coverage/Application/Command/FetchProjectCoverageCommand.php`
- Create: `backend/src/Coverage/Application/CommandHandler/FetchProjectCoverageHandler.php`
- Create: `backend/src/Shared/Domain/Event/ProjectCoverageFetchedEvent.php`
- Test: `backend/tests/Unit/Coverage/Application/CommandHandler/FetchProjectCoverageHandlerTest.php`

- [ ] **Step 1: Create FetchProjectCoverageCommand**

```php
<?php

declare(strict_types=1);

namespace App\Coverage\Application\Command;

final readonly class FetchProjectCoverageCommand
{
    public function __construct(
        public string $projectId,
        public string $syncId,
    ) {
    }
}
```

- [ ] **Step 2: Create ProjectCoverageFetchedEvent**

```php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

final readonly class ProjectCoverageFetchedEvent
{
    public function __construct(
        public string $projectId,
        public string $syncId,
        public string $projectName,
        public ?float $coveragePercent,
    ) {
    }
}
```

- [ ] **Step 3: Write FetchProjectCoverageHandler test**

```php
<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\ProviderType;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Coverage\Application\Command\FetchProjectCoverageCommand;
use App\Coverage\Application\CommandHandler\FetchProjectCoverageHandler;
use App\Coverage\Domain\Model\CoverageSnapshot;
use App\Coverage\Domain\Port\CoverageProviderInterface;
use App\Coverage\Domain\Repository\CoverageSnapshotRepositoryInterface;
use App\Coverage\Domain\ValueObject\CoverageResult;
use App\Coverage\Infrastructure\Provider\CoverageProviderRegistry;
use App\Shared\Domain\Event\ProjectCoverageFetchedEvent;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

describe('FetchProjectCoverageHandler', function (): void {
    it('fetches and persists coverage snapshot', function (): void {
        $projectId = Uuid::v7();
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getId')->andReturn($projectId);
        $project->shouldReceive('getName')->andReturn('back-api');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('getType')->andReturn(ProviderType::GitLab);
        $project->shouldReceive('getProvider')->andReturn($provider);

        $projectRepo = Mockery::mock(ProjectRepositoryInterface::class);
        $projectRepo->shouldReceive('findById')->with(Mockery::on(
            fn (Uuid $id) => $id->toRfc4122() === $projectId->toRfc4122()
        ))->andReturn($project);

        $result = new CoverageResult(82.3, 'abc123def456', 'main', '12345');

        $coverageProvider = Mockery::mock(CoverageProviderInterface::class);
        $coverageProvider->shouldReceive('fetchCoverage')->with($project)->andReturn($result);

        $registry = Mockery::mock(CoverageProviderRegistry::class);
        $registry->shouldReceive('resolve')->with(ProviderType::GitLab)->andReturn($coverageProvider);

        $snapshotRepo = Mockery::mock(CoverageSnapshotRepositoryInterface::class);
        $snapshotRepo->shouldReceive('save')->once()->with(Mockery::type(CoverageSnapshot::class));

        $eventBus = Mockery::mock(MessageBusInterface::class);
        $eventBus->shouldReceive('dispatch')->once()->with(Mockery::on(
            fn ($event) => $event instanceof ProjectCoverageFetchedEvent
                && $event->projectId === $projectId->toRfc4122()
                && $event->projectName === 'back-api'
                && $event->coveragePercent === 82.3
        ))->andReturn(new Envelope(new \stdClass()));

        $handler = new FetchProjectCoverageHandler($projectRepo, $registry, $snapshotRepo, $eventBus);
        $handler(new FetchProjectCoverageCommand($projectId->toRfc4122(), Uuid::v7()->toRfc4122()));
    });

    it('dispatches event with null coverage when provider is null', function (): void {
        $projectId = Uuid::v7();
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getId')->andReturn($projectId);
        $project->shouldReceive('getName')->andReturn('legacy-app');
        $project->shouldReceive('getProvider')->andReturn(null);

        $projectRepo = Mockery::mock(ProjectRepositoryInterface::class);
        $projectRepo->shouldReceive('findById')->andReturn($project);

        $registry = Mockery::mock(CoverageProviderRegistry::class);
        $snapshotRepo = Mockery::mock(CoverageSnapshotRepositoryInterface::class);
        $snapshotRepo->shouldNotReceive('save');

        $eventBus = Mockery::mock(MessageBusInterface::class);
        $eventBus->shouldReceive('dispatch')->once()->with(Mockery::on(
            fn ($event) => $event instanceof ProjectCoverageFetchedEvent
                && $event->coveragePercent === null
        ))->andReturn(new Envelope(new \stdClass()));

        $handler = new FetchProjectCoverageHandler($projectRepo, $registry, $snapshotRepo, $eventBus);
        $handler(new FetchProjectCoverageCommand($projectId->toRfc4122(), Uuid::v7()->toRfc4122()));
    });

    it('dispatches event with null coverage when fetch returns null', function (): void {
        $projectId = Uuid::v7();
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getId')->andReturn($projectId);
        $project->shouldReceive('getName')->andReturn('no-ci-project');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('getType')->andReturn(ProviderType::GitLab);
        $project->shouldReceive('getProvider')->andReturn($provider);

        $projectRepo = Mockery::mock(ProjectRepositoryInterface::class);
        $projectRepo->shouldReceive('findById')->andReturn($project);

        $coverageProvider = Mockery::mock(CoverageProviderInterface::class);
        $coverageProvider->shouldReceive('fetchCoverage')->andReturn(null);

        $registry = Mockery::mock(CoverageProviderRegistry::class);
        $registry->shouldReceive('resolve')->andReturn($coverageProvider);

        $snapshotRepo = Mockery::mock(CoverageSnapshotRepositoryInterface::class);
        $snapshotRepo->shouldNotReceive('save');

        $eventBus = Mockery::mock(MessageBusInterface::class);
        $eventBus->shouldReceive('dispatch')->once()->with(Mockery::on(
            fn ($event) => $event instanceof ProjectCoverageFetchedEvent
                && $event->coveragePercent === null
        ))->andReturn(new Envelope(new \stdClass()));

        $handler = new FetchProjectCoverageHandler($projectRepo, $registry, $snapshotRepo, $eventBus);
        $handler(new FetchProjectCoverageCommand($projectId->toRfc4122(), Uuid::v7()->toRfc4122()));
    });
});
```

- [ ] **Step 4: Run test to verify it fails**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Coverage/Application/CommandHandler/FetchProjectCoverageHandlerTest.php`
Expected: FAIL — class not found

- [ ] **Step 5: Implement FetchProjectCoverageHandler**

```php
<?php

declare(strict_types=1);

namespace App\Coverage\Application\CommandHandler;

use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Coverage\Application\Command\FetchProjectCoverageCommand;
use App\Coverage\Domain\Model\CoverageSnapshot;
use App\Coverage\Domain\Model\CoverageSource;
use App\Coverage\Domain\Repository\CoverageSnapshotRepositoryInterface;
use App\Coverage\Infrastructure\Provider\CoverageProviderRegistry;
use App\Shared\Domain\Event\ProjectCoverageFetchedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class FetchProjectCoverageHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private CoverageProviderRegistry $providerRegistry,
        private CoverageSnapshotRepositoryInterface $snapshotRepository,
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(FetchProjectCoverageCommand $command): void
    {
        $project = $this->projectRepository->findById(Uuid::fromString($command->projectId));
        if ($project === null) {
            return;
        }

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
            projectId: $project->getId()->toRfc4122(),
            syncId: $command->syncId,
            projectName: $project->getName(),
            coveragePercent: $coveragePercent,
        ));
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Coverage/Application/CommandHandler/FetchProjectCoverageHandlerTest.php`
Expected: PASS

- [ ] **Step 7: Add routing in messenger.yaml**

Add under `framework.messenger.routing`:

```yaml
App\Coverage\Application\Command\FetchProjectCoverageCommand: async
```

- [ ] **Step 8: Commit**

```bash
git add backend/src/Coverage/Application/ backend/src/Shared/Domain/Event/ProjectCoverageFetchedEvent.php backend/tests/Unit/Coverage/Application/ backend/config/packages/messenger.yaml
git commit -m "feat(coverage): add FetchProjectCoverageCommand and handler"
```

---

## Task 8: GlobalSyncCoverageProgressListener

**Files:**
- Create: `backend/src/Sync/Application/EventListener/GlobalSyncCoverageProgressListener.php`
- Test: `backend/tests/Unit/Sync/Application/EventListener/GlobalSyncCoverageProgressListenerTest.php`

- [ ] **Step 1: Write GlobalSyncCoverageProgressListener test**

```php
<?php

declare(strict_types=1);

use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\Event\ProjectCoverageFetchedEvent;
use App\Sync\Application\EventListener\GlobalSyncCoverageProgressListener;
use App\Sync\Domain\Model\GlobalSyncJob;
use App\Sync\Domain\Model\GlobalSyncStep;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use App\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

describe('GlobalSyncCoverageProgressListener', function (): void {
    it('increments progress on ProjectCoverageFetchedEvent', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncCoverage, 3);

        $syncRepo = Mockery::mock(GlobalSyncJobRepositoryInterface::class);
        $syncRepo->shouldReceive('findRunning')->andReturn($job);
        $syncRepo->shouldReceive('save')->once();

        $hub = Mockery::mock(HubInterface::class);
        $hub->shouldReceive('publish')->once()->with(Mockery::type(Update::class));

        $commandBus = Mockery::mock(MessageBusInterface::class);
        $depRepo = Mockery::mock(DependencyRepositoryInterface::class);
        $productRepo = Mockery::mock(ProductRepositoryInterface::class);

        $listener = new GlobalSyncCoverageProgressListener($syncRepo, $depRepo, $productRepo, $commandBus, $hub);
        $listener(new ProjectCoverageFetchedEvent(
            Uuid::v7()->toRfc4122(),
            $job->getId()->toRfc4122(),
            'back-api',
            82.3,
        ));

        expect($job->getStepProgress())->toBe(1);
    });

    it('transitions to SyncVersions when all coverage fetched', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncCoverage, 1);

        $syncRepo = Mockery::mock(GlobalSyncJobRepositoryInterface::class);
        $syncRepo->shouldReceive('findRunning')->andReturn($job);
        $syncRepo->shouldReceive('save')->twice();

        $hub = Mockery::mock(HubInterface::class);
        $hub->shouldReceive('publish')->twice();

        $depRepo = Mockery::mock(DependencyRepositoryInterface::class);
        $depRepo->shouldReceive('findUniquePackages')->andReturn(['pkg1', 'pkg2']);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findAll')->andReturn(['p1']);

        $commandBus = Mockery::mock(MessageBusInterface::class);
        $commandBus->shouldReceive('dispatch')->twice()->andReturn(new Envelope(new \stdClass()));

        $listener = new GlobalSyncCoverageProgressListener($syncRepo, $depRepo, $productRepo, $commandBus, $hub);
        $listener(new ProjectCoverageFetchedEvent(
            Uuid::v7()->toRfc4122(),
            $job->getId()->toRfc4122(),
            'back-api',
            82.3,
        ));

        expect($job->getCurrentStepName())->toBe('sync_versions')
            ->and($job->getStepTotal())->toBe(3);
    });

    it('ignores event if not on sync_coverage step', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncProjects, 5);

        $syncRepo = Mockery::mock(GlobalSyncJobRepositoryInterface::class);
        $syncRepo->shouldReceive('findRunning')->andReturn($job);
        $syncRepo->shouldNotReceive('save');

        $hub = Mockery::mock(HubInterface::class);
        $commandBus = Mockery::mock(MessageBusInterface::class);
        $depRepo = Mockery::mock(DependencyRepositoryInterface::class);
        $productRepo = Mockery::mock(ProductRepositoryInterface::class);

        $listener = new GlobalSyncCoverageProgressListener($syncRepo, $depRepo, $productRepo, $commandBus, $hub);
        $listener(new ProjectCoverageFetchedEvent(
            Uuid::v7()->toRfc4122(),
            Uuid::v7()->toRfc4122(),
            'back-api',
            82.3,
        ));

        expect($job->getStepProgress())->toBe(0);
    });

    it('ignores event if no running job', function (): void {
        $syncRepo = Mockery::mock(GlobalSyncJobRepositoryInterface::class);
        $syncRepo->shouldReceive('findRunning')->andReturn(null);

        $hub = Mockery::mock(HubInterface::class);
        $commandBus = Mockery::mock(MessageBusInterface::class);
        $depRepo = Mockery::mock(DependencyRepositoryInterface::class);
        $productRepo = Mockery::mock(ProductRepositoryInterface::class);

        $listener = new GlobalSyncCoverageProgressListener($syncRepo, $depRepo, $productRepo, $commandBus, $hub);
        $listener(new ProjectCoverageFetchedEvent(
            Uuid::v7()->toRfc4122(),
            Uuid::v7()->toRfc4122(),
            'back-api',
            82.3,
        ));
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Sync/Application/EventListener/GlobalSyncCoverageProgressListenerTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Implement GlobalSyncCoverageProgressListener**

```php
<?php

declare(strict_types=1);

namespace App\Sync\Application\EventListener;

use App\Dependency\Application\Command\SyncDependencyVersionsCommand;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\Event\ProjectCoverageFetchedEvent;
use App\Sync\Domain\Model\GlobalSyncJob;
use App\Sync\Domain\Model\GlobalSyncStep;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use App\VersionRegistry\Application\Command\SyncProductVersionsCommand;
use App\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class GlobalSyncCoverageProgressListener
{
    public function __construct(
        private GlobalSyncJobRepositoryInterface $repository,
        private DependencyRepositoryInterface $dependencyRepository,
        private ProductRepositoryInterface $productRepository,
        private MessageBusInterface $commandBus,
        private HubInterface $mercureHub,
    ) {
    }

    public function __invoke(ProjectCoverageFetchedEvent $event): void
    {
        $job = $this->repository->findRunning();
        if ($job === null) {
            return;
        }

        if ($job->getCurrentStepName() !== GlobalSyncStep::SyncCoverage->name()) {
            return;
        }

        $job->incrementProgress();
        $this->repository->save($job);

        $message = $event->coveragePercent !== null
            ? \sprintf('%s: %.1f%%', $event->projectName, $event->coveragePercent)
            : \sprintf('%s: n/a', $event->projectName);
        $this->publishProgress($job, $message);

        if ($job->getStepProgress() >= $job->getStepTotal()) {
            $this->transitionToSyncVersions($job);
        }
    }

    private function transitionToSyncVersions(GlobalSyncJob $job): void
    {
        $totalDeps = \count($this->dependencyRepository->findUniquePackages());
        $totalProducts = \count($this->productRepository->findAll());
        $job->startStep(GlobalSyncStep::SyncVersions, $totalDeps + $totalProducts);
        $this->repository->save($job);
        $this->publishProgress($job, null);

        $syncId = $job->getId()->toRfc4122();
        $this->commandBus->dispatch(new SyncDependencyVersionsCommand(syncId: $syncId));
        $this->commandBus->dispatch(new SyncProductVersionsCommand(syncId: $syncId));
    }

    private function publishProgress(GlobalSyncJob $job, ?string $message): void
    {
        $syncId = $job->getId()->toRfc4122();

        $this->mercureHub->publish(new Update(
            \sprintf('/global-sync/%s', $syncId),
            (string) \json_encode([
                'syncId' => $syncId,
                'status' => $job->getStatus()->value,
                'currentStep' => $job->getCurrentStep(),
                'currentStepName' => $job->getCurrentStepName(),
                'stepProgress' => $job->getStepProgress(),
                'stepTotal' => $job->getStepTotal(),
                'completedSteps' => $job->getCompletedStepNames(),
                'message' => $message,
            ]),
        ));
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Sync/Application/EventListener/GlobalSyncCoverageProgressListenerTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add backend/src/Sync/Application/EventListener/GlobalSyncCoverageProgressListener.php backend/tests/Unit/Sync/Application/EventListener/GlobalSyncCoverageProgressListenerTest.php
git commit -m "feat(sync): add GlobalSyncCoverageProgressListener"
```

---

## Task 9: Update Existing Sync Listeners

**Files:**
- Modify: `backend/src/Sync/Application/EventListener/GlobalSyncProgressListener.php`
- Modify: `backend/src/Sync/Application/EventListener/GlobalSyncVersionProgressListener.php`
- Modify: `backend/tests/Unit/Sync/Application/CommandHandler/GlobalSyncHandlerTest.php` (if affected)

- [ ] **Step 1: Update GlobalSyncProgressListener — transition to SyncCoverage instead of SyncVersions**

In `GlobalSyncProgressListener.php`, rename `transitionToStep2` to `transitionToSyncCoverage`. Replace the body:

**Before** (current `transitionToStep2`):
```php
private function transitionToStep2(GlobalSyncJob $job): void
{
    $totalDeps = \count($this->dependencyRepository->findUniquePackages());
    $totalProducts = \count($this->productRepository->findAll());
    $job->startStep(GlobalSyncStep::SyncVersions, $totalDeps + $totalProducts);
    $this->repository->save($job);
    $this->publishProgress($job);

    $syncId = $job->getId()->toRfc4122();
    $this->commandBus->dispatch(new SyncDependencyVersionsCommand(syncId: $syncId));
    $this->commandBus->dispatch(new SyncProductVersionsCommand(syncId: $syncId));
}
```

**After:**
```php
private function transitionToSyncCoverage(GlobalSyncJob $job): void
{
    $projects = $this->projectRepository->findAllWithProvider();
    $eligibleCount = \count(\array_filter($projects, fn ($p) => $p->getProvider() !== null));

    if ($eligibleCount === 0) {
        $this->skipToSyncVersions($job);
        return;
    }

    $job->startStep(GlobalSyncStep::SyncCoverage, $eligibleCount);
    $this->repository->save($job);
    $this->publishProgress($job);

    $syncId = $job->getId()->toRfc4122();
    foreach ($projects as $project) {
        if ($project->getProvider() !== null) {
            $this->commandBus->dispatch(new FetchProjectCoverageCommand(
                $project->getId()->toRfc4122(),
                $syncId,
            ));
        }
    }
}

private function skipToSyncVersions(GlobalSyncJob $job): void
{
    $totalDeps = \count($this->dependencyRepository->findUniquePackages());
    $totalProducts = \count($this->productRepository->findAll());
    $job->startStep(GlobalSyncStep::SyncVersions, $totalDeps + $totalProducts);
    $this->repository->save($job);
    $this->publishProgress($job);

    $syncId = $job->getId()->toRfc4122();
    $this->commandBus->dispatch(new SyncDependencyVersionsCommand(syncId: $syncId));
    $this->commandBus->dispatch(new SyncProductVersionsCommand(syncId: $syncId));
}
```

Add the import at the top:
```php
use App\Coverage\Application\Command\FetchProjectCoverageCommand;
```

Also inject `ProjectRepositoryInterface` into the constructor (it's already available in `GlobalSyncHandler` — check if `GlobalSyncProgressListener` already has it, otherwise add it).

Update the call site from `$this->transitionToStep2($job)` to `$this->transitionToSyncCoverage($job)`.

- [ ] **Step 2: Update GlobalSyncVersionProgressListener — step numbers**

In `GlobalSyncVersionProgressListener.php`, the step check currently compares against `GlobalSyncStep::SyncVersions->name()`. Since the enum value changed from 2 to 3, verify the check uses the enum name (string) not the value (int). If it uses `->name()` comparison, no change needed — the string `sync_versions` is still the same.

Verify `transitionToStep3` still works (ScanCve is now step 4 instead of 3). Since it uses `GlobalSyncStep::ScanCve` directly, the enum change handles it automatically. Rename method to `transitionToScanCve` for clarity.

- [ ] **Step 3: Run all Sync tests**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest tests/Unit/Sync`
Expected: PASS (fix any failures from step renumbering)

- [ ] **Step 4: Run full backend test suite**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add backend/src/Sync/Application/EventListener/ backend/tests/Unit/Sync/
git commit -m "feat(sync): wire SyncCoverage step into existing listeners"
```

---

## Task 10: Coverage API Endpoints

**Files:**
- Create: `backend/src/Coverage/Application/DTO/CoverageSummaryOutput.php`
- Create: `backend/src/Coverage/Application/DTO/CoverageProjectOutput.php`
- Create: `backend/src/Coverage/Application/DTO/CoverageDashboardOutput.php`
- Create: `backend/src/Coverage/Application/Query/GetCoverageDashboardQuery.php`
- Create: `backend/src/Coverage/Application/QueryHandler/GetCoverageDashboardQueryHandler.php`
- Create: `backend/src/Coverage/Application/Query/GetProjectCoverageHistoryQuery.php`
- Create: `backend/src/Coverage/Application/QueryHandler/GetProjectCoverageHistoryQueryHandler.php`
- Create: `backend/src/Coverage/Presentation/Controller/GetCoverageDashboardController.php`
- Create: `backend/src/Coverage/Presentation/Controller/GetProjectCoverageHistoryController.php`

- [ ] **Step 1: Create DTOs**

`CoverageSummaryOutput.php`:
```php
<?php

declare(strict_types=1);

namespace App\Coverage\Application\DTO;

final readonly class CoverageSummaryOutput
{
    public function __construct(
        public ?float $averageCoverage,
        public int $totalProjects,
        public int $coveredProjects,
        public int $aboveThreshold,
        public int $belowThreshold,
        public ?float $trend,
    ) {
    }
}
```

`CoverageProjectOutput.php`:
```php
<?php

declare(strict_types=1);

namespace App\Coverage\Application\DTO;

final readonly class CoverageProjectOutput
{
    public function __construct(
        public string $projectId,
        public string $projectName,
        public string $projectSlug,
        public ?float $coveragePercent,
        public ?float $trend,
        public ?string $source,
        public ?string $commitHash,
        public ?string $ref,
        public ?string $syncedAt,
    ) {
    }
}
```

`CoverageDashboardOutput.php`:
```php
<?php

declare(strict_types=1);

namespace App\Coverage\Application\DTO;

final readonly class CoverageDashboardOutput
{
    /** @param list<CoverageProjectOutput> $projects */
    public function __construct(
        public CoverageSummaryOutput $summary,
        public array $projects,
    ) {
    }
}
```

- [ ] **Step 2: Create GetCoverageDashboardQuery + Handler**

`GetCoverageDashboardQuery.php`:
```php
<?php

declare(strict_types=1);

namespace App\Coverage\Application\Query;

final readonly class GetCoverageDashboardQuery
{
}
```

`GetCoverageDashboardQueryHandler.php`:
```php
<?php

declare(strict_types=1);

namespace App\Coverage\Application\QueryHandler;

use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Coverage\Application\DTO\CoverageDashboardOutput;
use App\Coverage\Application\DTO\CoverageProjectOutput;
use App\Coverage\Application\DTO\CoverageSummaryOutput;
use App\Coverage\Domain\Repository\CoverageSnapshotRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetCoverageDashboardQueryHandler
{
    private const THRESHOLD = 80.0;

    public function __construct(
        private CoverageSnapshotRepositoryInterface $snapshotRepository,
        private ProjectRepositoryInterface $projectRepository,
    ) {
    }

    public function __invoke(GetCoverageDashboardQuery $query): CoverageDashboardOutput
    {
        $allProjects = $this->projectRepository->findAll(1, 1000);
        $latestSnapshots = $this->snapshotRepository->findLatestPerProject();
        $previousSnapshots = $this->snapshotRepository->findPreviousPerProject();

        $latestByProject = [];
        foreach ($latestSnapshots as $snapshot) {
            $latestByProject[$snapshot->getProjectId()->toRfc4122()] = $snapshot;
        }

        $previousByProject = [];
        foreach ($previousSnapshots as $snapshot) {
            $previousByProject[$snapshot->getProjectId()->toRfc4122()] = $snapshot;
        }

        $projects = [];
        $coveredPercents = [];

        foreach ($allProjects as $project) {
            $pid = $project->getId()->toRfc4122();
            $latest = $latestByProject[$pid] ?? null;
            $previous = $previousByProject[$pid] ?? null;

            $percent = $latest?->getCoveragePercent();
            $trend = ($latest !== null && $previous !== null)
                ? \round($latest->getCoveragePercent() - $previous->getCoveragePercent(), 1)
                : null;

            if ($percent !== null) {
                $coveredPercents[] = $percent;
            }

            $projects[] = new CoverageProjectOutput(
                projectId: $pid,
                projectName: $project->getName(),
                projectSlug: $project->getSlug(),
                coveragePercent: $percent,
                trend: $trend,
                source: $latest?->getSource()->value,
                commitHash: $latest?->getCommitHash(),
                ref: $latest?->getRef(),
                syncedAt: $latest?->getCreatedAt()->format(\DateTimeInterface::ATOM),
            );
        }

        $coveredCount = \count($coveredPercents);
        $avg = $coveredCount > 0 ? \round(\array_sum($coveredPercents) / $coveredCount, 1) : null;
        $above = \count(\array_filter($coveredPercents, fn (float $p) => $p >= self::THRESHOLD));

        $summary = new CoverageSummaryOutput(
            averageCoverage: $avg,
            totalProjects: \count($allProjects),
            coveredProjects: $coveredCount,
            aboveThreshold: $above,
            belowThreshold: $coveredCount - $above,
            trend: null,
        );

        return new CoverageDashboardOutput($summary, $projects);
    }
}
```

- [ ] **Step 3: Create GetProjectCoverageHistoryQuery + Handler**

`GetProjectCoverageHistoryQuery.php`:
```php
<?php

declare(strict_types=1);

namespace App\Coverage\Application\Query;

final readonly class GetProjectCoverageHistoryQuery
{
    public function __construct(
        public string $projectSlug,
    ) {
    }
}
```

`GetProjectCoverageHistoryQueryHandler.php`:
```php
<?php

declare(strict_types=1);

namespace App\Coverage\Application\QueryHandler;

use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Coverage\Domain\Repository\CoverageSnapshotRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetProjectCoverageHistoryQueryHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private CoverageSnapshotRepositoryInterface $snapshotRepository,
    ) {
    }

    public function __invoke(GetProjectCoverageHistoryQuery $query): array
    {
        $project = $this->projectRepository->findBySlug($query->projectSlug);
        if ($project === null) {
            return [];
        }

        $snapshots = $this->snapshotRepository->findAllByProject($project->getId());

        return [
            'project' => [
                'id' => $project->getId()->toRfc4122(),
                'name' => $project->getName(),
                'slug' => $project->getSlug(),
            ],
            'snapshots' => \array_map(fn ($s) => [
                'commitHash' => $s->getCommitHash(),
                'coveragePercent' => $s->getCoveragePercent(),
                'source' => $s->getSource()->value,
                'ref' => $s->getRef(),
                'pipelineId' => $s->getPipelineId(),
                'createdAt' => $s->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ], $snapshots),
        ];
    }
}
```

- [ ] **Step 4: Create controllers**

`GetCoverageDashboardController.php`:
```php
<?php

declare(strict_types=1);

namespace App\Coverage\Presentation\Controller;

use App\Coverage\Application\Query\GetCoverageDashboardQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/coverage', name: 'api_coverage_dashboard', methods: ['GET'])]
final class GetCoverageDashboardController
{
    use HandleTrait;

    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $result = $this->handle(new GetCoverageDashboardQuery());

        return new JsonResponse([
            'summary' => [
                'averageCoverage' => $result->summary->averageCoverage,
                'totalProjects' => $result->summary->totalProjects,
                'coveredProjects' => $result->summary->coveredProjects,
                'aboveThreshold' => $result->summary->aboveThreshold,
                'belowThreshold' => $result->summary->belowThreshold,
                'trend' => $result->summary->trend,
            ],
            'projects' => \array_map(fn ($p) => [
                'projectId' => $p->projectId,
                'projectName' => $p->projectName,
                'projectSlug' => $p->projectSlug,
                'coveragePercent' => $p->coveragePercent,
                'trend' => $p->trend,
                'source' => $p->source,
                'commitHash' => $p->commitHash,
                'ref' => $p->ref,
                'syncedAt' => $p->syncedAt,
            ], $result->projects),
        ]);
    }
}
```

`GetProjectCoverageHistoryController.php`:
```php
<?php

declare(strict_types=1);

namespace App\Coverage\Presentation\Controller;

use App\Coverage\Application\Query\GetProjectCoverageHistoryQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/coverage/{projectSlug}', name: 'api_coverage_project_history', methods: ['GET'])]
final class GetProjectCoverageHistoryController
{
    use HandleTrait;

    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(string $projectSlug): JsonResponse
    {
        $result = $this->handle(new GetProjectCoverageHistoryQuery($projectSlug));

        if ($result === []) {
            return new JsonResponse(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($result);
    }
}
```

- [ ] **Step 5: Run full backend tests**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add backend/src/Coverage/Application/DTO/ backend/src/Coverage/Application/Query/ backend/src/Coverage/Application/QueryHandler/ backend/src/Coverage/Presentation/
git commit -m "feat(coverage): add API endpoints — GET /coverage and GET /coverage/:slug"
```

---

## Task 11: Frontend — Update Sync Types + Banner

**Files:**
- Modify: `frontend/src/shared/types/globalSync.ts`
- Modify: `frontend/src/shared/components/SyncProgressBanner.vue`
- Modify: `frontend/src/shared/components/SyncButton.vue`

- [ ] **Step 1: Update globalSync.ts**

```typescript
export type SyncStepName = 'sync_projects' | 'sync_coverage' | 'sync_versions' | 'scan_cve';
export type SyncStatus = 'running' | 'completed' | 'failed';

export interface GlobalSyncState {
  syncId: string;
  status: SyncStatus;
  currentStep: 1 | 2 | 3 | 4;
  currentStepName: SyncStepName;
  stepProgress: number;
  stepTotal: number;
  completedSteps: SyncStepName[];
  message?: string;
}

export const STEP_LABELS: Record<SyncStepName, string> = {
  sync_projects: 'Sync Projets',
  sync_coverage: 'Sync Coverage',
  sync_versions: 'Sync Versions',
  scan_cve: 'Scan CVE',
};

export const STEP_ORDER: SyncStepName[] = ['sync_projects', 'sync_coverage', 'sync_versions', 'scan_cve'];
```

- [ ] **Step 2: Update SyncProgressBanner.vue**

Update the template to render 4 breadcrumbs instead of 3. The component already iterates over `STEP_ORDER`, so the template should work automatically after updating the types. Verify that the step connectors and progress bar render correctly for 4 steps.

- [ ] **Step 3: Update SyncButton.vue**

Change the step label from `Step ${currentSync.value?.currentStep}/3` to `Step ${currentSync.value?.currentStep}/4`.

- [ ] **Step 4: Run existing frontend sync tests**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm vitest run --reporter=dot src/shared`
Expected: PASS (fix any snapshot/assertion failures from 3→4 step count)

- [ ] **Step 5: Commit**

```bash
git add frontend/src/shared/types/globalSync.ts frontend/src/shared/components/SyncProgressBanner.vue frontend/src/shared/components/SyncButton.vue
git commit -m "feat(frontend): update sync types and banner for 4-step workflow"
```

---

## Task 12: Frontend — Coverage Types + Service + Store

**Files:**
- Create: `frontend/src/coverage/types/index.ts`
- Create: `frontend/src/coverage/services/coverage.service.ts`
- Create: `frontend/src/coverage/stores/coverage.ts`

- [ ] **Step 1: Create coverage types**

```typescript
export interface CoverageSummary {
  averageCoverage: number | null;
  totalProjects: number;
  coveredProjects: number;
  aboveThreshold: number;
  belowThreshold: number;
  trend: number | null;
}

export interface CoverageProject {
  projectId: string;
  projectName: string;
  projectSlug: string;
  coveragePercent: number | null;
  trend: number | null;
  source: string | null;
  commitHash: string | null;
  ref: string | null;
  syncedAt: string | null;
}

export interface CoverageDashboard {
  summary: CoverageSummary;
  projects: CoverageProject[];
}

export interface CoverageSnapshot {
  commitHash: string;
  coveragePercent: number;
  source: string;
  ref: string;
  pipelineId: string | null;
  createdAt: string;
}

export interface ProjectCoverageHistory {
  project: { id: string; name: string; slug: string };
  snapshots: CoverageSnapshot[];
}
```

- [ ] **Step 2: Create coverage service**

```typescript
import type { CoverageDashboard, ProjectCoverageHistory } from '@/coverage/types';
import { api } from '@/shared/utils/api';

export const coverageService = {
  async getDashboard(): Promise<CoverageDashboard> {
    return api.get<CoverageDashboard>('/coverage');
  },

  async getProjectHistory(projectSlug: string): Promise<ProjectCoverageHistory> {
    return api.get<ProjectCoverageHistory>(`/coverage/${projectSlug}`);
  },
};
```

- [ ] **Step 3: Create coverage store**

```typescript
import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { CoverageDashboard } from '@/coverage/types';
import { coverageService } from '@/coverage/services/coverage.service';

export const useCoverageStore = defineStore('coverage', () => {
  const dashboard = ref<CoverageDashboard | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function fetchDashboard(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      dashboard.value = await coverageService.getDashboard();
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch coverage data';
    } finally {
      loading.value = false;
    }
  }

  return { dashboard, loading, error, fetchDashboard };
});
```

- [ ] **Step 4: Commit**

```bash
git add frontend/src/coverage/
git commit -m "feat(frontend): add coverage types, service, and store"
```

---

## Task 13: Frontend — Coverage Page + Components

**Files:**
- Create: `frontend/src/coverage/components/CoverageSummaryCard.vue`
- Create: `frontend/src/coverage/components/CoverageProjectList.vue`
- Create: `frontend/src/coverage/pages/CoverageDashboard.vue`
- Create: `frontend/src/coverage/routes.ts`
- Modify: `frontend/src/app/router.ts`

- [ ] **Step 1: Create CoverageSummaryCard component**

```vue
<script setup lang="ts">
import type { CoverageSummary } from '@/coverage/types';

defineProps<{ summary: CoverageSummary }>();
</script>

<template>
  <div class="grid grid-cols-2 gap-4 lg:grid-cols-5" data-testid="coverage-summary">
    <div class="rounded-lg bg-surface p-4">
      <p class="text-sm text-text-muted">Moyenne</p>
      <p class="text-2xl font-bold">
        {{ summary.averageCoverage !== null ? `${summary.averageCoverage}%` : '—' }}
      </p>
    </div>
    <div class="rounded-lg bg-surface p-4">
      <p class="text-sm text-text-muted">Projets couverts</p>
      <p class="text-2xl font-bold">{{ summary.coveredProjects }} / {{ summary.totalProjects }}</p>
    </div>
    <div class="rounded-lg bg-surface p-4">
      <p class="text-sm text-text-muted">&ge; 80%</p>
      <p class="text-2xl font-bold text-green-500">{{ summary.aboveThreshold }}</p>
    </div>
    <div class="rounded-lg bg-surface p-4">
      <p class="text-sm text-text-muted">&lt; 80%</p>
      <p class="text-2xl font-bold text-orange-500">{{ summary.belowThreshold }}</p>
    </div>
    <div class="rounded-lg bg-surface p-4">
      <p class="text-sm text-text-muted">Tendance</p>
      <p class="text-2xl font-bold">
        <span v-if="summary.trend !== null && summary.trend > 0" class="text-green-500">&uarr; +{{ summary.trend }}</span>
        <span v-else-if="summary.trend !== null && summary.trend < 0" class="text-red-500">&darr; {{ summary.trend }}</span>
        <span v-else>—</span>
      </p>
    </div>
  </div>
</template>
```

- [ ] **Step 2: Create CoverageProjectList component**

```vue
<script setup lang="ts">
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import type { CoverageProject } from '@/coverage/types';

const props = defineProps<{ projects: CoverageProject[] }>();
const router = useRouter();

type SortKey = 'coveragePercent' | 'projectName' | 'syncedAt';
const sortKey = ref<SortKey>('coveragePercent');
const sortDesc = ref(true);

const sorted = computed(() => {
  return [...props.projects].sort((a, b) => {
    const aVal = a[sortKey.value];
    const bVal = b[sortKey.value];
    if (aVal === null && bVal === null) return 0;
    if (aVal === null) return 1;
    if (bVal === null) return -1;
    const cmp = aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
    return sortDesc.value ? -cmp : cmp;
  });
});

function toggleSort(key: SortKey): void {
  if (sortKey.value === key) {
    sortDesc.value = !sortDesc.value;
  } else {
    sortKey.value = key;
    sortDesc.value = key === 'coveragePercent';
  }
}

function coverageColor(percent: number | null): string {
  if (percent === null) return 'bg-gray-200';
  if (percent >= 80) return 'bg-green-500';
  if (percent >= 60) return 'bg-orange-400';
  return 'bg-red-500';
}

function trendDisplay(trend: number | null): string {
  if (trend === null) return '—';
  if (trend > 0) return `+${trend}`;
  return `${trend}`;
}

function trendColor(trend: number | null): string {
  if (trend === null) return '';
  if (trend > 0) return 'text-green-500';
  if (trend < 0) return 'text-red-500';
  return 'text-text-muted';
}

function goToProject(slug: string): void {
  router.push({ name: 'coverage-project', params: { slug } });
}
</script>

<template>
  <div class="overflow-x-auto" data-testid="coverage-project-list">
    <table class="w-full text-left text-sm">
      <thead>
        <tr class="border-b border-border text-text-muted">
          <th class="cursor-pointer px-4 py-3" @click="toggleSort('projectName')">Projet</th>
          <th class="cursor-pointer px-4 py-3" @click="toggleSort('coveragePercent')">Coverage</th>
          <th class="px-4 py-3">Tendance</th>
          <th class="px-4 py-3">Source</th>
          <th class="px-4 py-3">Commit</th>
          <th class="cursor-pointer px-4 py-3" @click="toggleSort('syncedAt')">Date</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="project in sorted"
          :key="project.projectId"
          class="cursor-pointer border-b border-border transition-colors hover:bg-surface"
          @click="goToProject(project.projectSlug)"
        >
          <td class="px-4 py-3 font-medium">{{ project.projectName }}</td>
          <td class="px-4 py-3">
            <div v-if="project.coveragePercent !== null" class="flex items-center gap-2">
              <div class="h-2 w-20 overflow-hidden rounded-full bg-gray-200">
                <div
                  :class="coverageColor(project.coveragePercent)"
                  :style="{ width: `${project.coveragePercent}%` }"
                  class="h-full rounded-full transition-all"
                />
              </div>
              <span>{{ project.coveragePercent }}%</span>
            </div>
            <span v-else class="text-text-muted">—</span>
          </td>
          <td class="px-4 py-3" :class="trendColor(project.trend)">
            {{ trendDisplay(project.trend) }}
          </td>
          <td class="px-4 py-3 text-text-muted">{{ project.source ?? '—' }}</td>
          <td class="px-4 py-3 font-mono text-xs text-text-muted">
            {{ project.commitHash ? project.commitHash.substring(0, 7) : '—' }}
          </td>
          <td class="px-4 py-3 text-text-muted">
            {{ project.syncedAt ? new Date(project.syncedAt).toLocaleDateString('fr-FR') : '—' }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
```

- [ ] **Step 3: Create CoverageDashboard page**

```vue
<script setup lang="ts">
import { onMounted } from 'vue';
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue';
import SyncButton from '@/shared/components/SyncButton.vue';
import CoverageSummaryCard from '@/coverage/components/CoverageSummaryCard.vue';
import CoverageProjectList from '@/coverage/components/CoverageProjectList.vue';
import { useCoverageStore } from '@/coverage/stores/coverage';
import { useGlobalSync } from '@/shared/composables/useGlobalSync';

const coverageStore = useCoverageStore();
const { onStepCompleted } = useGlobalSync();

onMounted(() => {
  coverageStore.fetchDashboard();
});

onStepCompleted((step) => {
  if (step === 'sync_coverage') {
    coverageStore.fetchDashboard();
  }
});
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 p-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Coverage</h1>
        <SyncButton />
      </div>

      <div v-if="coverageStore.loading" class="text-text-muted">Chargement...</div>

      <div v-else-if="coverageStore.error" class="text-red-500">
        {{ coverageStore.error }}
      </div>

      <template v-else-if="coverageStore.dashboard">
        <CoverageSummaryCard :summary="coverageStore.dashboard.summary" />
        <CoverageProjectList :projects="coverageStore.dashboard.projects" />
      </template>
    </div>
  </DashboardLayout>
</template>
```

- [ ] **Step 4: Create coverage routes**

```typescript
import type { RouteRecordRaw } from 'vue-router';

export const coverageRoutes: RouteRecordRaw[] = [
  {
    component: () => import('@/coverage/pages/CoverageDashboard.vue'),
    name: 'coverage-dashboard',
    path: '/coverage',
  },
  {
    component: () => import('@/coverage/pages/CoverageDashboard.vue'),
    name: 'coverage-project',
    path: '/coverage/:slug',
  },
];
```

Note: Both routes point to `CoverageDashboard.vue` for now. The `:slug` route will be updated in a future iteration to use a dedicated `CoverageProjectHistory.vue` page.

- [ ] **Step 5: Register routes in router.ts**

In `frontend/src/app/router.ts`, add:

```typescript
import { coverageRoutes } from '@/coverage/routes';
```

And add `...coverageRoutes` to the `routes` array.

- [ ] **Step 6: Add navigation link**

In the sidebar component (`frontend/src/shared/components/AppSidebar.vue`), add a navigation link to `/coverage` following the same pattern as existing links (icon + label). Use a chart/shield icon.

- [ ] **Step 7: Run frontend tests**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm vitest run`
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add frontend/src/coverage/ frontend/src/app/router.ts frontend/src/shared/components/AppSidebar.vue
git commit -m "feat(frontend): add coverage dashboard page with summary and project list"
```

---

## Task 14: Frontend — Coverage Tests

**Files:**
- Create: `frontend/tests/coverage/components/CoverageSummaryCard.test.ts`
- Create: `frontend/tests/coverage/components/CoverageProjectList.test.ts`
- Create: `frontend/tests/coverage/pages/CoverageDashboard.test.ts`

- [ ] **Step 1: Write CoverageSummaryCard test**

```typescript
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import CoverageSummaryCard from '@/coverage/components/CoverageSummaryCard.vue';
import type { CoverageSummary } from '@/coverage/types';

describe('CoverageSummaryCard', () => {
  const summary: CoverageSummary = {
    averageCoverage: 74.2,
    totalProjects: 15,
    coveredProjects: 10,
    aboveThreshold: 7,
    belowThreshold: 3,
    trend: 1.3,
  };

  it('renders average coverage', () => {
    const wrapper = mount(CoverageSummaryCard, { props: { summary } });
    expect(wrapper.text()).toContain('74.2%');
  });

  it('renders project counts', () => {
    const wrapper = mount(CoverageSummaryCard, { props: { summary } });
    expect(wrapper.text()).toContain('10 / 15');
  });

  it('renders threshold counts', () => {
    const wrapper = mount(CoverageSummaryCard, { props: { summary } });
    expect(wrapper.text()).toContain('7');
    expect(wrapper.text()).toContain('3');
  });

  it('renders dash when average is null', () => {
    const noAvg = { ...summary, averageCoverage: null };
    const wrapper = mount(CoverageSummaryCard, { props: { summary: noAvg } });
    expect(wrapper.find('[data-testid="coverage-summary"]').text()).toContain('—');
  });
});
```

- [ ] **Step 2: Write CoverageProjectList test**

```typescript
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import CoverageProjectList from '@/coverage/components/CoverageProjectList.vue';
import type { CoverageProject } from '@/coverage/types';

const mockRouter = { push: vi.fn() };
vi.mock('vue-router', () => ({ useRouter: () => mockRouter }));

describe('CoverageProjectList', () => {
  const projects: CoverageProject[] = [
    {
      projectId: '1',
      projectName: 'back-api',
      projectSlug: 'back-api',
      coveragePercent: 82.3,
      trend: 2.1,
      source: 'ci_gitlab',
      commitHash: 'a3f21bc4e5d6f7890123456789abcdef01234567',
      ref: 'main',
      syncedAt: '2026-03-31T14:30:00Z',
    },
    {
      projectId: '2',
      projectName: 'front-client',
      projectSlug: 'front-client',
      coveragePercent: null,
      trend: null,
      source: null,
      commitHash: null,
      ref: null,
      syncedAt: null,
    },
  ];

  it('renders all projects', () => {
    const wrapper = mount(CoverageProjectList, { props: { projects } });
    expect(wrapper.findAll('tbody tr')).toHaveLength(2);
  });

  it('displays coverage percentage', () => {
    const wrapper = mount(CoverageProjectList, { props: { projects } });
    expect(wrapper.text()).toContain('82.3%');
  });

  it('displays dash for null coverage', () => {
    const wrapper = mount(CoverageProjectList, { props: { projects } });
    const rows = wrapper.findAll('tbody tr');
    expect(rows[1].text()).toContain('—');
  });

  it('displays truncated commit hash', () => {
    const wrapper = mount(CoverageProjectList, { props: { projects } });
    expect(wrapper.text()).toContain('a3f21bc');
  });
});
```

- [ ] **Step 3: Run coverage tests**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm vitest run tests/coverage`
Expected: PASS

- [ ] **Step 4: Run full frontend test suite**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm vitest run`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add frontend/tests/coverage/
git commit -m "test(frontend): add coverage dashboard component tests"
```

---

## Task 15: Final Verification

- [ ] **Step 1: Run full backend test suite**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/pest`
Expected: All tests pass

- [ ] **Step 2: Run backend static analysis**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/phpstan analyse`
Expected: 0 errors

- [ ] **Step 3: Run backend lint**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T backend vendor/bin/php-cs-fixer fix --dry-run --diff`
Expected: No fixable issues (or fix them)

- [ ] **Step 4: Run full frontend test suite**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm vitest run`
Expected: All tests pass

- [ ] **Step 5: Run frontend lint**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm lint`
Expected: No issues

- [ ] **Step 6: Verify CI dashboard still works**

Run: `make ci` (or check that the existing CI dashboard script still runs cleanly)

- [ ] **Step 7: Final commit if any fixes**

```bash
git add -A
git commit -m "fix: address lint and type issues from coverage sync feature"
```
