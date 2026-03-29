<?php

declare(strict_types=1);

use App\Dependency\Application\Command\SyncDependencyVersionsCommand;
use App\Dependency\Application\Command\SyncSingleDependencyVersionCommand;
use App\Dependency\Application\CommandHandler\SyncDependencyVersionsHandler;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

function stubSyncVersionsDepRepo(array $packages): DependencyRepositoryInterface
{
    return new class ($packages) implements DependencyRepositoryInterface {
        public function __construct(private readonly array $packages)
        {
        }
        public function findById(Uuid $id): ?Dependency
        {
            return null;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function count(): int
        {
            return 0;
        }
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function save(Dependency $dependency): void
        {
        }
        public function delete(Dependency $dependency): void
        {
        }
        public function countByProjectId(Uuid $projectId): int
        {
            return 0;
        }
        public function deleteByProjectId(Uuid $projectId): void
        {
        }
        public function findFiltered(int $page, int $perPage, array $filters = []): array
        {
            return [];
        }
        public function countFiltered(array $filters = []): int
        {
            return 0;
        }
        public function getStats(array $filters = []): array
        {
            return ['total' => 0, 'outdated' => 0, 'totalVulnerabilities' => 0];
        }
        public function findUniquePackages(): array
        {
            return $this->packages;
        }
        public function findByName(string $name, string $packageManager): array
        {
            return [];
        }
    };
}

function spySyncVersionsBus(): object
{
    return new class () implements MessageBusInterface {
        /** @var list<object> */
        public array $dispatched = [];
        public function dispatch(object $message, array $stamps = []): Envelope
        {
            $msg = $message instanceof Envelope ? $message->getMessage() : $message;
            $this->dispatched[] = $msg;
            return Envelope::wrap($message, $stamps);
        }
    };
}

describe('SyncDependencyVersionsHandler', function () {
    it('dispatches SyncSingleDependencyVersionCommand for each unique package', function () {
        $depRepo = \stubSyncVersionsDepRepo([
            ['name' => 'vue', 'packageManager' => 'npm'],
            ['name' => 'symfony/http-kernel', 'packageManager' => 'composer'],
        ]);
        $bus = \spySyncVersionsBus();

        $handler = new SyncDependencyVersionsHandler($depRepo, $bus);
        $synced = $handler(new SyncDependencyVersionsCommand());

        expect($synced)->toBe(2);
        expect($bus->dispatched)->toHaveCount(2);
        expect($bus->dispatched[0])->toBeInstanceOf(SyncSingleDependencyVersionCommand::class);
        expect($bus->dispatched[0]->packageName)->toBe('vue');
        expect($bus->dispatched[0]->packageManager)->toBe('npm');
        expect($bus->dispatched[0]->index)->toBe(1);
        expect($bus->dispatched[0]->total)->toBe(2);
        expect($bus->dispatched[1]->packageName)->toBe('symfony/http-kernel');
        expect($bus->dispatched[1]->index)->toBe(2);
    });

    it('filters by packageNames when provided', function () {
        $depRepo = \stubSyncVersionsDepRepo([
            ['name' => 'vue', 'packageManager' => 'npm'],
            ['name' => 'react', 'packageManager' => 'npm'],
            ['name' => 'symfony/http-kernel', 'packageManager' => 'composer'],
        ]);
        $bus = \spySyncVersionsBus();

        $handler = new SyncDependencyVersionsHandler($depRepo, $bus);
        $synced = $handler(new SyncDependencyVersionsCommand(packageNames: ['vue']));

        expect($synced)->toBe(1);
        expect($bus->dispatched)->toHaveCount(1);
        expect($bus->dispatched[0]->packageName)->toBe('vue');
    });

    it('returns 0 when no packages to sync', function () {
        $depRepo = \stubSyncVersionsDepRepo([]);
        $bus = \spySyncVersionsBus();

        $handler = new SyncDependencyVersionsHandler($depRepo, $bus);
        $synced = $handler(new SyncDependencyVersionsCommand());

        expect($synced)->toBe(0);
        expect($bus->dispatched)->toBeEmpty();
    });

    it('passes syncId to dispatched commands', function () {
        $depRepo = \stubSyncVersionsDepRepo([
            ['name' => 'vue', 'packageManager' => 'npm'],
        ]);
        $bus = \spySyncVersionsBus();

        $handler = new SyncDependencyVersionsHandler($depRepo, $bus);
        $handler(new SyncDependencyVersionsCommand(syncId: 'sync-123'));

        expect($bus->dispatched[0]->syncId)->toBe('sync-123');
    });
});
