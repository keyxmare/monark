# Plan: Global Sync Workflow (Backend)

**Date**: 2026-03-31
**Objectif**: Unifier les syncs en un seul workflow 3-steps avec suivi de progression via Mercure.
**Architecture**: DDD/CQRS, Symfony 8, PHP 8.4, Doctrine ORM, Pest 4
**Spec**: `docs/superpowers/specs/2026-03-31-global-sync-workflow-design.md`

---

## Vue d'ensemble

```
POST /api/v1/sync
  → GlobalSyncJob créé (status=running, step=1)
  → GlobalSyncCommand dispatché (async)
        ↓
  GlobalSyncHandler (async)
    Step 1: ScanProjectCommand × N projets → progress → Mercure
    Step 2: SyncDependencyVersionsCommand + SyncProductVersionsCommand → Mercure
    Step 3: stub → completed → Mercure

GET /api/v1/sync/current
  → GlobalSyncJob en cours ou null
```

**Topic Mercure**: `/global-sync/{syncId}`

---

## Tâche 1 — Migration DB

**Fichiers créés**:
- `backend/migrations/Version20260331100000.php`

**Steps**:

- [ ] Créer la migration Doctrine

```php
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create global_sync_jobs table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE global_sync_jobs (
            id UUID NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'running\',
            current_step INT NOT NULL DEFAULT 1,
            current_step_name VARCHAR(50) NOT NULL DEFAULT \'sync_projects\',
            step_progress INT NOT NULL DEFAULT 0,
            step_total INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_global_sync_status ON global_sync_jobs (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE global_sync_jobs');
    }
}
```

---

## Tâche 2 — Entité GlobalSyncJob

**Fichiers créés**:
- `backend/src/Sync/Domain/Model/GlobalSyncJob.php`
- `backend/src/Sync/Domain/Model/GlobalSyncStatus.php`
- `backend/src/Sync/Domain/Model/GlobalSyncStep.php`

**Steps**:

- [ ] Créer l'enum `GlobalSyncStatus`
- [ ] Créer l'enum `GlobalSyncStep`
- [ ] Créer l'entité `GlobalSyncJob`
- [ ] Tests unitaires (`backend/tests/Unit/Sync/Domain/Model/GlobalSyncJobTest.php`)

```php
<?php

declare(strict_types=1);

namespace App\Sync\Domain\Model;

enum GlobalSyncStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
```

```php
<?php

declare(strict_types=1);

namespace App\Sync\Domain\Model;

enum GlobalSyncStep: int
{
    case SyncProjects = 1;
    case SyncVersions = 2;
    case ScanCve = 3;

    public function name(): string
    {
        return match ($this) {
            self::SyncProjects => 'sync_projects',
            self::SyncVersions => 'sync_versions',
            self::ScanCve => 'scan_cve',
        };
    }

    public function next(): ?self
    {
        return match ($this) {
            self::SyncProjects => self::SyncVersions,
            self::SyncVersions => self::ScanCve,
            self::ScanCve => null,
        };
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Sync\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'global_sync_jobs')]
final class GlobalSyncJob
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'string', enumType: GlobalSyncStatus::class)]
    private GlobalSyncStatus $status;

    #[ORM\Column]
    private int $currentStep;

    #[ORM\Column]
    private string $currentStepName;

    #[ORM\Column]
    private int $stepProgress;

    #[ORM\Column]
    private int $stepTotal;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $completedAt;

    private function __construct(Uuid $id)
    {
        $this->id = $id;
        $this->status = GlobalSyncStatus::Running;
        $this->currentStep = GlobalSyncStep::SyncProjects->value;
        $this->currentStepName = GlobalSyncStep::SyncProjects->name();
        $this->stepProgress = 0;
        $this->stepTotal = 0;
        $this->createdAt = new DateTimeImmutable();
        $this->completedAt = null;
    }

    public static function create(): self
    {
        return new self(Uuid::v7());
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getStatus(): GlobalSyncStatus
    {
        return $this->status;
    }

    public function getCurrentStep(): int
    {
        return $this->currentStep;
    }

    public function getCurrentStepName(): string
    {
        return $this->currentStepName;
    }

    public function getStepProgress(): int
    {
        return $this->stepProgress;
    }

    public function getStepTotal(): int
    {
        return $this->stepTotal;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function startStep(GlobalSyncStep $step, int $total): void
    {
        $this->currentStep = $step->value;
        $this->currentStepName = $step->name();
        $this->stepProgress = 0;
        $this->stepTotal = $total;
    }

    public function incrementProgress(): void
    {
        ++$this->stepProgress;
    }

    public function complete(): void
    {
        $this->status = GlobalSyncStatus::Completed;
        $this->completedAt = new DateTimeImmutable();
    }

    public function markFailed(): void
    {
        $this->status = GlobalSyncStatus::Failed;
        $this->completedAt = new DateTimeImmutable();
    }

    public function isRunning(): bool
    {
        return $this->status === GlobalSyncStatus::Running;
    }

    /** @return list<string> */
    public function getCompletedStepNames(): array
    {
        $completed = [];
        for ($i = 1; $i < $this->currentStep; $i++) {
            $step = GlobalSyncStep::from($i);
            $completed[] = $step->name();
        }

        if ($this->status === GlobalSyncStatus::Completed) {
            $completed[] = $this->currentStepName;
        }

        return $completed;
    }
}
```

**Tests unitaires**:

```php
<?php

declare(strict_types=1);

use App\Sync\Domain\Model\GlobalSyncJob;
use App\Sync\Domain\Model\GlobalSyncStatus;
use App\Sync\Domain\Model\GlobalSyncStep;

describe('GlobalSyncJob', function (): void {
    it('creates with running status and step 1', function (): void {
        $job = GlobalSyncJob::create();

        expect($job->getStatus())->toBe(GlobalSyncStatus::Running)
            ->and($job->getCurrentStep())->toBe(1)
            ->and($job->getCurrentStepName())->toBe('sync_projects')
            ->and($job->getStepProgress())->toBe(0)
            ->and($job->getStepTotal())->toBe(0)
            ->and($job->getCompletedAt())->toBeNull();
    });

    it('starts a step with correct total', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncVersions, 42);

        expect($job->getCurrentStep())->toBe(2)
            ->and($job->getCurrentStepName())->toBe('sync_versions')
            ->and($job->getStepTotal())->toBe(42)
            ->and($job->getStepProgress())->toBe(0);
    });

    it('increments progress', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncProjects, 5);
        $job->incrementProgress();
        $job->incrementProgress();

        expect($job->getStepProgress())->toBe(2);
    });

    it('completes with timestamp', function (): void {
        $job = GlobalSyncJob::create();
        $job->complete();

        expect($job->getStatus())->toBe(GlobalSyncStatus::Completed)
            ->and($job->getCompletedAt())->not->toBeNull();
    });

    it('marks failed', function (): void {
        $job = GlobalSyncJob::create();
        $job->markFailed();

        expect($job->getStatus())->toBe(GlobalSyncStatus::Failed);
    });

    it('returns completed step names', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::SyncVersions, 10);

        expect($job->getCompletedStepNames())->toBe(['sync_projects']);
    });

    it('returns all steps completed when done', function (): void {
        $job = GlobalSyncJob::create();
        $job->startStep(GlobalSyncStep::ScanCve, 0);
        $job->complete();

        expect($job->getCompletedStepNames())->toBe(['sync_projects', 'sync_versions', 'scan_cve']);
    });
});
```

---

## Tâche 3 — Repository GlobalSyncJob

**Fichiers créés**:
- `backend/src/Sync/Domain/Repository/GlobalSyncJobRepositoryInterface.php`
- `backend/src/Sync/Infrastructure/Repository/DoctrineGlobalSyncJobRepository.php`

**Steps**:

- [ ] Créer l'interface du repository
- [ ] Créer l'implémentation Doctrine
- [ ] Enregistrer dans `config/services.yaml`

```php
<?php

declare(strict_types=1);

namespace App\Sync\Domain\Repository;

use App\Sync\Domain\Model\GlobalSyncJob;
use Symfony\Component\Uid\Uuid;

interface GlobalSyncJobRepositoryInterface
{
    public function save(GlobalSyncJob $job): void;

    public function findById(Uuid $id): ?GlobalSyncJob;

    public function findRunning(): ?GlobalSyncJob;
}
```

```php
<?php

declare(strict_types=1);

namespace App\Sync\Infrastructure\Repository;

use App\Sync\Domain\Model\GlobalSyncJob;
use App\Sync\Domain\Model\GlobalSyncStatus;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineGlobalSyncJobRepository implements GlobalSyncJobRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function save(GlobalSyncJob $job): void
    {
        $this->em->persist($job);
        $this->em->flush();
    }

    public function findById(Uuid $id): ?GlobalSyncJob
    {
        return $this->em->find(GlobalSyncJob::class, $id);
    }

    public function findRunning(): ?GlobalSyncJob
    {
        return $this->em->getRepository(GlobalSyncJob::class)->findOneBy([
            'status' => GlobalSyncStatus::Running,
        ]);
    }
}
```

**`config/services.yaml`** — ajouter le binding :

```yaml
App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface:
    class: App\Sync\Infrastructure\Repository\DoctrineGlobalSyncJobRepository
```

---

## Tâche 4 — GlobalSyncCommand + Handler

**Fichiers créés**:
- `backend/src/Sync/Application/Command/GlobalSyncCommand.php`
- `backend/src/Sync/Application/CommandHandler/GlobalSyncHandler.php`
- `backend/src/Sync/Application/DTO/GlobalSyncJobOutput.php`

**Steps**:

- [ ] Créer la commande `GlobalSyncCommand`
- [ ] Créer le DTO `GlobalSyncJobOutput`
- [ ] Créer `GlobalSyncHandler` — orchestre les 3 steps séquentiellement
- [ ] Ajouter le routing async dans `messenger.yaml`
- [ ] Tests (`backend/tests/Unit/Sync/Application/CommandHandler/GlobalSyncHandlerTest.php`)

```php
<?php

declare(strict_types=1);

namespace App\Sync\Application\Command;

final readonly class GlobalSyncCommand
{
    public function __construct(
        public string $syncId,
    ) {
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Sync\Application\DTO;

final readonly class GlobalSyncJobOutput
{
    public function __construct(
        public string $syncId,
        public string $status,
        public int $currentStep,
    ) {
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Sync\Application\CommandHandler;

use App\Catalog\Application\Command\ScanProjectCommand;
use App\Catalog\Application\Command\SyncMergeRequestsCommand;
use App\Catalog\Application\Command\SyncProjectMetadataCommand;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Dependency\Application\Command\SyncDependencyVersionsCommand;
use App\Sync\Application\Command\GlobalSyncCommand;
use App\Sync\Domain\Model\GlobalSyncStep;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use App\VersionRegistry\Application\Command\SyncProductVersionsCommand;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class GlobalSyncHandler
{
    public function __construct(
        private GlobalSyncJobRepositoryInterface $globalSyncJobRepository,
        private ProjectRepositoryInterface $projectRepository,
        private MessageBusInterface $commandBus,
        private HubInterface $mercureHub,
    ) {
    }

    public function __invoke(GlobalSyncCommand $command): void
    {
        $job = $this->globalSyncJobRepository->findById(Uuid::fromString($command->syncId));
        if ($job === null) {
            return;
        }

        try {
            $this->runStep1($command->syncId);
            $this->runStep2($command->syncId);
            $this->runStep3($command->syncId);

            $job = $this->globalSyncJobRepository->findById(Uuid::fromString($command->syncId));
            $job->complete();
            $this->globalSyncJobRepository->save($job);

            $this->publishMercure($command->syncId, $job);
        } catch (\Throwable $e) {
            $job = $this->globalSyncJobRepository->findById(Uuid::fromString($command->syncId));
            $job->markFailed();
            $this->globalSyncJobRepository->save($job);

            throw $e;
        }
    }

    private function runStep1(string $syncId): void
    {
        $projects = $this->projectRepository->findAllWithProvider();
        $total = \count($projects);

        $job = $this->globalSyncJobRepository->findById(Uuid::fromString($syncId));
        $job->startStep(GlobalSyncStep::SyncProjects, $total);
        $this->globalSyncJobRepository->save($job);
        $this->publishMercure($syncId, $job);

        foreach ($projects as $project) {
            $projectId = $project->getId()->toRfc4122();

            $this->commandBus->dispatch(
                new ScanProjectCommand($projectId),
                [new DispatchAfterCurrentBusStamp()],
            );
            $this->commandBus->dispatch(
                new SyncProjectMetadataCommand($projectId),
                [new DispatchAfterCurrentBusStamp()],
            );
            $this->commandBus->dispatch(
                new SyncMergeRequestsCommand($projectId, false, $syncId),
                [new DispatchAfterCurrentBusStamp()],
            );

            $job = $this->globalSyncJobRepository->findById(Uuid::fromString($syncId));
            $job->incrementProgress();
            $this->globalSyncJobRepository->save($job);
            $this->publishMercure($syncId, $job);
        }
    }

    private function runStep2(string $syncId): void
    {
        $job = $this->globalSyncJobRepository->findById(Uuid::fromString($syncId));
        $job->startStep(GlobalSyncStep::SyncVersions, 0);
        $this->globalSyncJobRepository->save($job);
        $this->publishMercure($syncId, $job);

        $this->commandBus->dispatch(
            new SyncDependencyVersionsCommand(syncId: $syncId),
            [new DispatchAfterCurrentBusStamp()],
        );
        $this->commandBus->dispatch(
            new SyncProductVersionsCommand(syncId: $syncId),
            [new DispatchAfterCurrentBusStamp()],
        );
    }

    private function runStep3(string $syncId): void
    {
        $job = $this->globalSyncJobRepository->findById(Uuid::fromString($syncId));
        $job->startStep(GlobalSyncStep::ScanCve, 0);
        $this->globalSyncJobRepository->save($job);
        $this->publishMercure($syncId, $job);
    }

    private function publishMercure(string $syncId, \App\Sync\Domain\Model\GlobalSyncJob $job): void
    {
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
            ]),
        ));
    }
}
```

**`config/packages/messenger.yaml`** — ajouter le routing :

```yaml
App\Sync\Application\Command\GlobalSyncCommand: async
```

> **Note architecture** : Le handler est dispatché en async. Les sub-commands (ScanProjectCommand, etc.) sont dispatched avec `DispatchAfterCurrentBusStamp` mais le handler attend leur complétion avant de passer au step suivant. Pour Step 1, la progression est comptée projet par projet dans le même handler (séquentiel). Pour Step 2, les deux commandes de versions sont dispatchées en async — la progression fine est déjà gérée par les handlers existants qui publient sur Mercure avec leur propre `syncId`.

**Tests unitaires** :

```php
<?php

declare(strict_types=1);

use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Sync\Application\Command\GlobalSyncCommand;
use App\Sync\Application\CommandHandler\GlobalSyncHandler;
use App\Sync\Domain\Model\GlobalSyncJob;
use App\Sync\Domain\Model\GlobalSyncStatus;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Messenger\MessageBusInterface;

describe('GlobalSyncHandler', function (): void {
    beforeEach(function (): void {
        $this->repo = Mockery::mock(GlobalSyncJobRepositoryInterface::class);
        $this->projects = Mockery::mock(ProjectRepositoryInterface::class);
        $this->bus = Mockery::mock(MessageBusInterface::class);
        $this->hub = Mockery::mock(HubInterface::class);

        $this->handler = new GlobalSyncHandler(
            $this->repo,
            $this->projects,
            $this->bus,
            $this->hub,
        );
    });

    it('completes job when no projects', function (): void {
        $job = GlobalSyncJob::create();
        $syncId = $job->getId()->toRfc4122();

        $this->repo->allows('findById')->andReturn($job);
        $this->repo->allows('save');
        $this->projects->allows('findAllWithProvider')->andReturn([]);
        $this->bus->allows('dispatch')->andReturn(new \Symfony\Component\Messenger\Envelope(new \stdClass()));
        $this->hub->allows('publish');

        ($this->handler)(new GlobalSyncCommand($syncId));

        expect($job->getStatus())->toBe(GlobalSyncStatus::Completed);
    });

    it('marks job as failed on exception', function (): void {
        $job = GlobalSyncJob::create();
        $syncId = $job->getId()->toRfc4122();

        $this->repo->allows('findById')->andReturn($job);
        $this->repo->allows('save');
        $this->projects->allows('findAllWithProvider')->andThrow(new \RuntimeException('db error'));
        $this->hub->allows('publish');

        expect(fn () => ($this->handler)(new GlobalSyncCommand($syncId)))
            ->toThrow(\RuntimeException::class);

        expect($job->getStatus())->toBe(GlobalSyncStatus::Failed);
    });
});
```

---

## Tâche 5 — POST /api/v1/sync (controller)

**Fichiers créés**:
- `backend/src/Sync/Presentation/Controller/StartGlobalSyncController.php`

**Steps**:

- [ ] Créer le controller POST
- [ ] Déclarer la route
- [ ] Tests (`backend/tests/Feature/Sync/StartGlobalSyncControllerTest.php`)

```php
<?php

declare(strict_types=1);

namespace App\Sync\Presentation\Controller;

use App\Shared\Application\DTO\ApiResponse;
use App\Sync\Application\Command\GlobalSyncCommand;
use App\Sync\Domain\Model\GlobalSyncJob;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;
use Symfony\Component\Routing\Attribute\Route;

final readonly class StartGlobalSyncController
{
    public function __construct(
        private GlobalSyncJobRepositoryInterface $globalSyncJobRepository,
        private MessageBusInterface $commandBus,
    ) {
    }

    #[Route('/api/v1/sync', name: 'global_sync_start', methods: ['POST'])]
    #[OA\Post(
        summary: 'Start global sync workflow',
        tags: ['Sync'],
        responses: [
            new OA\Response(response: 202, description: 'Sync started'),
            new OA\Response(response: 409, description: 'Sync already running'),
        ],
    )]
    public function __invoke(): JsonResponse
    {
        $running = $this->globalSyncJobRepository->findRunning();
        if ($running !== null) {
            return new JsonResponse(
                ApiResponse::error('A sync is already running', 409)->toArray(),
                409,
            );
        }

        $job = GlobalSyncJob::create();
        $this->globalSyncJobRepository->save($job);
        $syncId = $job->getId()->toRfc4122();

        $this->commandBus->dispatch(
            new GlobalSyncCommand($syncId),
            [new DispatchAfterCurrentBusStamp()],
        );

        return new JsonResponse(
            ApiResponse::success([
                'syncId' => $syncId,
                'status' => $job->getStatus()->value,
                'currentStep' => $job->getCurrentStep(),
            ])->toArray(),
            202,
        );
    }
}
```

**Tests feature** :

```php
<?php

declare(strict_types=1);

use App\Sync\Domain\Model\GlobalSyncJob;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;

describe('POST /api/v1/sync', function (): void {
    it('returns 202 and creates job', function (): void {
        $response = $this->postJson('/api/v1/sync');

        $response->assertStatus(202);
        $response->assertJsonPath('data.status', 'running');
        $response->assertJsonPath('data.currentStep', 1);
    });

    it('returns 409 when a sync is already running', function (): void {
        $repo = app(GlobalSyncJobRepositoryInterface::class);
        $job = GlobalSyncJob::create();
        $repo->save($job);

        $response = $this->postJson('/api/v1/sync');

        $response->assertStatus(409);
    });
});
```

---

## Tâche 6 — GET /api/v1/sync/current (controller)

**Fichiers créés**:
- `backend/src/Sync/Presentation/Controller/GetCurrentGlobalSyncController.php`

**Steps**:

- [ ] Créer le controller GET
- [ ] Tests (`backend/tests/Feature/Sync/GetCurrentGlobalSyncControllerTest.php`)

```php
<?php

declare(strict_types=1);

namespace App\Sync\Presentation\Controller;

use App\Shared\Application\DTO\ApiResponse;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetCurrentGlobalSyncController
{
    public function __construct(
        private GlobalSyncJobRepositoryInterface $globalSyncJobRepository,
    ) {
    }

    #[Route('/api/v1/sync/current', name: 'global_sync_current', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get current running global sync',
        tags: ['Sync'],
        responses: [
            new OA\Response(response: 200, description: 'Current sync or null'),
        ],
    )]
    public function __invoke(): JsonResponse
    {
        $job = $this->globalSyncJobRepository->findRunning();

        if ($job === null) {
            return new JsonResponse(ApiResponse::success(null)->toArray());
        }

        return new JsonResponse(ApiResponse::success([
            'syncId' => $job->getId()->toRfc4122(),
            'status' => $job->getStatus()->value,
            'currentStep' => $job->getCurrentStep(),
            'currentStepName' => $job->getCurrentStepName(),
            'stepProgress' => $job->getStepProgress(),
            'stepTotal' => $job->getStepTotal(),
            'completedSteps' => $job->getCompletedStepNames(),
            'createdAt' => $job->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ])->toArray());
    }
}
```

**Tests feature** :

```php
<?php

declare(strict_types=1);

use App\Sync\Domain\Model\GlobalSyncJob;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;

describe('GET /api/v1/sync/current', function (): void {
    it('returns null when no sync running', function (): void {
        $response = $this->getJson('/api/v1/sync/current');

        $response->assertStatus(200);
        $response->assertJsonPath('data', null);
    });

    it('returns running job data', function (): void {
        $repo = app(GlobalSyncJobRepositoryInterface::class);
        $job = GlobalSyncJob::create();
        $repo->save($job);

        $response = $this->getJson('/api/v1/sync/current');

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'running');
        $response->assertJsonPath('data.currentStep', 1);
    });
});
```

---

## Tâche 7 — Adapter les handlers existants (progression Step 2)

**Fichiers modifiés**:
- `backend/src/Dependency/Application/Command/SyncDependencyVersionsCommand.php` — ajouter `syncId` si absent
- `backend/src/VersionRegistry/Application/Command/SyncProductVersionsCommand.php` — ajouter `syncId` si absent
- `backend/src/Dependency/Application/CommandHandler/SyncSingleDependencyVersionHandler.php` — publier sur `/global-sync/{syncId}` si syncId présent
- `backend/src/VersionRegistry/Application/CommandHandler/SyncSingleProductHandler.php` — idem

**Steps**:

- [ ] Vérifier que `SyncDependencyVersionsCommand` a déjà un `syncId` (oui d'après le code existant)
- [ ] Vérifier que `SyncProductVersionsCommand` a un `syncId`
- [ ] Dans `SyncSingleDependencyVersionHandler`, publier sur `/global-sync/{syncId}` en plus du topic existant `/sync-jobs/{syncId}` (backward compatible)
- [ ] Dans `SyncSingleProductHandler`, même adaptation

**Pattern d'adaptation dans les handlers existants** :

```php
if ($command->syncId !== null) {
    $this->mercureHub->publish(new Update(
        \sprintf('/global-sync/%s', $command->syncId),
        (string) \json_encode([
            'syncId' => $command->syncId,
            'status' => 'running',
            'currentStep' => 2,
            'currentStepName' => 'sync_versions',
            'stepProgress' => $command->index,
            'stepTotal' => $command->total,
            'completedSteps' => ['sync_projects'],
            'message' => $command->packageName,
        ]),
    ));
}
```

> **Note** : L'adaptation est minimale et non-breaking. Les handlers existants continuent de publier sur leur topic habituel pour la compatibilité ascendante.

---

## Tâche 8 — Cleanup SyncAllProjectsController

**Fichiers modifiés**:
- `backend/src/Catalog/Presentation/Controller/SyncAllProjectsController.php`

**Steps**:

- [ ] Évaluer si le endpoint `/api/v1/catalog/sync-all` reste utile (sync par provider uniquement)
- [ ] Supprimer `SyncAllProjectsCommand` sans `providerId` si complètement remplacé par le nouveau workflow
- [ ] Conserver la route `/api/v1/catalog/providers/{id}/sync-all` — elle reste valide pour sync ciblé

> **Décision**: Le nouveau workflow global remplace le bouton "sync all" générique. La route par provider est conservée. Le nettoyage consiste à supprimer la route `catalog_sync_all` et sa méthode `syncAll()` si elle n'est plus référencée dans le frontend.

---

## Tâche 9 — Structure bounded context Sync

**Fichiers créés** (structure DDD) :

```
backend/src/Sync/
  Domain/
    Model/
      GlobalSyncJob.php
      GlobalSyncStatus.php
      GlobalSyncStep.php
    Repository/
      GlobalSyncJobRepositoryInterface.php
  Application/
    Command/
      GlobalSyncCommand.php
    CommandHandler/
      GlobalSyncHandler.php
    DTO/
      GlobalSyncJobOutput.php
  Infrastructure/
    Repository/
      DoctrineGlobalSyncJobRepository.php
  Presentation/
    Controller/
      StartGlobalSyncController.php
      GetCurrentGlobalSyncController.php
```

> Le bounded context `Sync` est indépendant. Il dépend de `Catalog` et `Dependency` pour dispatcher leurs commandes — dépendance unidirectionnelle acceptable dans une architecture CQRS.

---

## Tâche 10 — Vérification finale

**Steps**:

- [ ] `make test` — tous les tests passent
- [ ] `make phpstan` — niveau max sans erreur
- [ ] `make migration` — migration appliquée proprement
- [ ] Test manuel via `curl POST /api/v1/sync` → vérifier Mercure topic `/global-sync/{id}`
- [ ] `GET /api/v1/sync/current` en cours de sync → retourne le job
- [ ] `POST /api/v1/sync` une 2ème fois → 409
- [ ] Après complétion → `GET /api/v1/sync/current` retourne null

---

## Ordre d'exécution recommandé

```
1 → 2 → 3 → 9 (structure) → 4 → 5 → 6 → 7 → 8 → 10
```

Tâches 5 et 6 peuvent se faire en parallèle après la tâche 4.
