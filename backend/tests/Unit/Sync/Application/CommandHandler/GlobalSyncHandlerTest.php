<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Sync\Application\Command\GlobalSyncCommand;
use App\Sync\Application\CommandHandler\GlobalSyncHandler;
use App\Sync\Domain\Model\GlobalSyncJob;
use App\Sync\Domain\Model\GlobalSyncStatus;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

function stubGlobalSyncJobRepo(GlobalSyncJob $job): GlobalSyncJobRepositoryInterface
{
    return new class ($job) implements GlobalSyncJobRepositoryInterface {
        public function __construct(private readonly GlobalSyncJob $job)
        {
        }

        public function save(GlobalSyncJob $job): void
        {
        }

        public function findById(Uuid $id): ?GlobalSyncJob
        {
            return $this->job;
        }

        public function findRunning(): ?GlobalSyncJob
        {
            return $this->job->isRunning() ? $this->job : null;
        }
    };
}

function stubGlobalProjectRepo(array $projects = []): ProjectRepositoryInterface
{
    return new class ($projects) implements ProjectRepositoryInterface {
        public function __construct(private readonly array $projects)
        {
        }

        public function findById(Uuid $id): ?Project
        {
            return null;
        }

        public function findBySlug(string $slug): ?Project
        {
            return null;
        }

        public function findByExternalIdAndProvider(string $externalId, Uuid $providerId): ?Project
        {
            return null;
        }

        public function findExternalIdMapByProvider(Uuid $providerId): array
        {
            return [];
        }

        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }

        public function findByProviderId(Uuid $providerId): array
        {
            return [];
        }

        public function findAllWithProvider(): array
        {
            return $this->projects;
        }

        public function count(): int
        {
            return \count($this->projects);
        }

        public function save(Project $project): void
        {
        }

        public function delete(Project $project): void
        {
        }
    };
}

function stubGlobalSyncThrowingProjectRepo(): ProjectRepositoryInterface
{
    return new class () implements ProjectRepositoryInterface {
        public function findById(Uuid $id): ?Project
        {
            return null;
        }

        public function findBySlug(string $slug): ?Project
        {
            return null;
        }

        public function findByExternalIdAndProvider(string $externalId, Uuid $providerId): ?Project
        {
            return null;
        }

        public function findExternalIdMapByProvider(Uuid $providerId): array
        {
            return [];
        }

        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }

        public function findByProviderId(Uuid $providerId): array
        {
            return [];
        }

        public function findAllWithProvider(): array
        {
            throw new \RuntimeException('db error');
        }

        public function count(): int
        {
            return 0;
        }

        public function save(Project $project): void
        {
        }

        public function delete(Project $project): void
        {
        }
    };
}

function stubGlobalSyncCommandBus(): MessageBusInterface
{
    return new class () implements MessageBusInterface {
        public function dispatch(object $message, array $stamps = []): Envelope
        {
            return Envelope::wrap($message, $stamps);
        }
    };
}

function stubGlobalSyncMercureHub(): HubInterface
{
    return new class () implements HubInterface {
        public function getPublicUrl(): string
        {
            return 'http://localhost/.well-known/mercure';
        }

        public function getFactory(): ?TokenFactoryInterface
        {
            return null;
        }

        public function publish(Update $update): string
        {
            return '';
        }
    };
}

describe('GlobalSyncHandler', function (): void {
    it('completes job when no projects', function (): void {
        $job = GlobalSyncJob::create();
        $syncId = $job->getId()->toRfc4122();

        $handler = new GlobalSyncHandler(
            \stubGlobalSyncJobRepo($job),
            \stubGlobalProjectRepo([]),
            \stubGlobalSyncCommandBus(),
            \stubGlobalSyncMercureHub(),
            new \Psr\Log\NullLogger(),
        );

        ($handler)(new GlobalSyncCommand($syncId));

        expect($job->getStatus())->toBe(GlobalSyncStatus::Completed);
    });

    it('marks job as failed on exception', function (): void {
        $job = GlobalSyncJob::create();
        $syncId = $job->getId()->toRfc4122();

        $handler = new GlobalSyncHandler(
            \stubGlobalSyncJobRepo($job),
            \stubGlobalSyncThrowingProjectRepo(),
            \stubGlobalSyncCommandBus(),
            \stubGlobalSyncMercureHub(),
            new \Psr\Log\NullLogger(),
        );

        expect(static fn () => ($handler)(new GlobalSyncCommand($syncId)))
            ->toThrow(\RuntimeException::class);

        expect($job->getStatus())->toBe(GlobalSyncStatus::Failed);
    });

    it('returns early when job not found', function (): void {
        $repo = new class () implements GlobalSyncJobRepositoryInterface {
            public function save(GlobalSyncJob $job): void
            {
            }

            public function findById(Uuid $id): ?GlobalSyncJob
            {
                return null;
            }

            public function findRunning(): ?GlobalSyncJob
            {
                return null;
            }
        };

        $handler = new GlobalSyncHandler(
            $repo,
            \stubGlobalProjectRepo([]),
            \stubGlobalSyncCommandBus(),
            \stubGlobalSyncMercureHub(),
            new \Psr\Log\NullLogger(),
        );

        ($handler)(new GlobalSyncCommand(Uuid::v7()->toRfc4122()));
    });
});
