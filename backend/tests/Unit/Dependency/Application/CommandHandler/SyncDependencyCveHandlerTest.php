<?php

declare(strict_types=1);

use App\Dependency\Application\Command\SyncDependencyCveCommand;
use App\Dependency\Application\CommandHandler\SyncDependencyCveHandler;
use App\Dependency\Domain\Event\DependencyCveSyncedEvent;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\DTO\OsvVulnerability;
use App\Shared\Domain\Port\OsvClientInterface;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use App\Shared\Domain\ValueObject\Severity;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

function stubCveDepRepo(array $dependencies = []): DependencyRepositoryInterface
{
    return new class ($dependencies) implements DependencyRepositoryInterface {
        /** @var list<Dependency> */
        public array $saved = [];
        public function __construct(private readonly array $deps)
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
            return $this->deps;
        }
        public function save(Dependency $dependency): void
        {
            $this->saved[] = $dependency;
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
        public function getStatsSingle(array $filters = []): array
        {
            return ['total' => 0, 'outdated' => 0, 'totalVulnerabilities' => 0];
        }
        public function findUniquePackages(): array
        {
            return [];
        }
        public function findByName(string $name, string $packageManager): array
        {
            return [];
        }
        public function findByNameManagerAndProjectId(string $name, string $packageManager, Uuid $projectId): ?Dependency
        {
            return null;
        }
        public function findFilteredWithVersionDates(int $page, int $perPage, array $filters = []): array
        {
            return [];
        }
    };
}

function stubOsvClient(array $batchResults = []): OsvClientInterface
{
    return new class ($batchResults) implements OsvClientInterface {
        public array $queriedBatches = [];
        public function __construct(private readonly array $batchResults)
        {
        }
        public function queryPackage(string $ecosystem, string $name, string $version): array
        {
            return [];
        }
        public function queryBatch(array $queries): array
        {
            $this->queriedBatches[] = $queries;
            return $this->batchResults;
        }
    };
}

function spyCveEventBus(): object
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

describe('SyncDependencyCveHandler', function () {
    it('creates vulnerabilities from OSV results', function () {
        $projectId = Uuid::v7();
        $dep = Dependency::create(
            name: 'symfony/http-kernel',
            currentVersion: '6.4.0',
            latestVersion: '7.2.0',
            ltsVersion: '6.4.12',
            packageManager: PackageManager::Composer,
            type: DependencyType::Runtime,
            isOutdated: true,
            projectId: $projectId,
        );

        $depRepo = \stubCveDepRepo([$dep]);
        $osvClient = \stubOsvClient([
            [
                new OsvVulnerability(
                    id: 'GHSA-xxxx',
                    cveId: 'CVE-2024-0001',
                    summary: 'Remote code execution',
                    severity: Severity::Critical,
                    cvssScore: 9.8,
                    patchedVersion: '6.4.13',
                    references: [],
                    publishedAt: new DateTimeImmutable(),
                ),
            ],
        ]);
        $eventBus = \spyCveEventBus();

        $handler = new SyncDependencyCveHandler($depRepo, $osvClient, $eventBus);
        $handler(new SyncDependencyCveCommand($projectId->toRfc4122()));

        expect($dep->getVulnerabilityCount())->toBe(1);
        expect($depRepo->saved)->toHaveCount(1);
        expect($eventBus->dispatched)->toHaveCount(1);
        expect($eventBus->dispatched[0])->toBeInstanceOf(DependencyCveSyncedEvent::class);
        expect($eventBus->dispatched[0]->vulnerabilitiesFound)->toBe(1);
    });

    it('skips already known CVEs', function () {
        $projectId = Uuid::v7();
        $dep = Dependency::create(
            name: 'symfony/http-kernel',
            currentVersion: '6.4.0',
            latestVersion: '7.2.0',
            ltsVersion: '6.4.12',
            packageManager: PackageManager::Composer,
            type: DependencyType::Runtime,
            isOutdated: true,
            projectId: $projectId,
        );

        $dep->reportVulnerability(
            cveId: 'CVE-2024-0001',
            severity: Severity::Critical,
            title: 'Already known',
            description: 'Already known',
            patchedVersion: '6.4.13',
        );
        expect($dep->getVulnerabilityCount())->toBe(1);

        $depRepo = \stubCveDepRepo([$dep]);
        $osvClient = \stubOsvClient([
            [
                new OsvVulnerability(
                    id: 'GHSA-xxxx',
                    cveId: 'CVE-2024-0001',
                    summary: 'Remote code execution',
                    severity: Severity::Critical,
                    cvssScore: 9.8,
                    patchedVersion: '6.4.13',
                    references: [],
                    publishedAt: new DateTimeImmutable(),
                ),
            ],
        ]);
        $eventBus = \spyCveEventBus();

        $handler = new SyncDependencyCveHandler($depRepo, $osvClient, $eventBus);
        $handler(new SyncDependencyCveCommand($projectId->toRfc4122()));

        expect($dep->getVulnerabilityCount())->toBe(1);
        expect($eventBus->dispatched[0]->vulnerabilitiesFound)->toBe(1);
    });

    it('dispatches event with zero when no vulns found', function () {
        $projectId = Uuid::v7();
        $dep = Dependency::create(
            name: 'vue',
            currentVersion: '3.5.0',
            latestVersion: '3.5.0',
            ltsVersion: '3.5.0',
            packageManager: PackageManager::Npm,
            type: DependencyType::Runtime,
            isOutdated: false,
            projectId: $projectId,
        );

        $depRepo = \stubCveDepRepo([$dep]);
        $osvClient = \stubOsvClient([[]]);
        $eventBus = \spyCveEventBus();

        $handler = new SyncDependencyCveHandler($depRepo, $osvClient, $eventBus);
        $handler(new SyncDependencyCveCommand($projectId->toRfc4122()));

        expect($eventBus->dispatched)->toHaveCount(1);
        expect($eventBus->dispatched[0])->toBeInstanceOf(DependencyCveSyncedEvent::class);
        expect($eventBus->dispatched[0]->vulnerabilitiesFound)->toBe(0);
    });

    it('dispatches event with zero when no dependencies exist', function () {
        $projectId = Uuid::v7();
        $depRepo = \stubCveDepRepo([]);
        $osvClient = \stubOsvClient([]);
        $eventBus = \spyCveEventBus();

        $handler = new SyncDependencyCveHandler($depRepo, $osvClient, $eventBus);
        $handler(new SyncDependencyCveCommand($projectId->toRfc4122()));

        expect($eventBus->dispatched)->toHaveCount(1);
        expect($eventBus->dispatched[0]->vulnerabilitiesFound)->toBe(0);
    });
});
