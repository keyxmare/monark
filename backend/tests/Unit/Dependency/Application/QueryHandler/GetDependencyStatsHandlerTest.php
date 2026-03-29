<?php

declare(strict_types=1);

use App\Dependency\Application\DTO\DependencyStatsOutput;
use App\Dependency\Application\Query\GetDependencyStatsQuery;
use App\Dependency\Application\QueryHandler\GetDependencyStatsHandler;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubDepStatsRepo(array $stats, ?array &$receivedFilters = null): DependencyRepositoryInterface
{
    return new class ($stats, $receivedFilters) implements DependencyRepositoryInterface {
        public function __construct(private readonly array $stats, private ?array &$receivedFilters)
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

        public function save(Dependency $dependency): void
        {
        }

        public function delete(Dependency $dependency): void
        {
        }

        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array
        {
            return [];
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

        public function findUniquePackages(): array
        {
            return [];
        }

        public function findByName(string $name, string $packageManager): array
        {
            return [];
        }

        public function getStats(array $filters = []): array
        {
            $this->receivedFilters = $filters;

            return $this->stats;
        }
    };
}

describe('GetDependencyStatsHandler', function () {
    it('returns correct stats with all fields', function () {
        $repo = stubDepStatsRepo([
            'total' => 50,
            'outdated' => 12,
            'totalVulnerabilities' => 3,
        ]);
        $handler = new GetDependencyStatsHandler($repo);

        $result = $handler(new GetDependencyStatsQuery());

        expect($result)->toBeInstanceOf(DependencyStatsOutput::class);
        expect($result->total)->toBe(50);
        expect($result->outdated)->toBe(12);
        expect($result->upToDate)->toBe(38);
        expect($result->totalVulnerabilities)->toBe(3);
    });

    it('calculates upToDate as total minus outdated', function () {
        $repo = stubDepStatsRepo([
            'total' => 100,
            'outdated' => 25,
            'totalVulnerabilities' => 7,
        ]);
        $handler = new GetDependencyStatsHandler($repo);

        $result = $handler(new GetDependencyStatsQuery());

        expect($result->upToDate)->toBe(75);
        expect($result->total - $result->outdated)->toBe($result->upToDate);
    });

    it('returns zero upToDate when all are outdated', function () {
        $repo = stubDepStatsRepo([
            'total' => 10,
            'outdated' => 10,
            'totalVulnerabilities' => 0,
        ]);
        $handler = new GetDependencyStatsHandler($repo);

        $result = $handler(new GetDependencyStatsQuery());

        expect($result->upToDate)->toBe(0);
        expect($result->outdated)->toBe(10);
        expect($result->total)->toBe(10);
        expect($result->totalVulnerabilities)->toBe(0);
    });

    it('returns all upToDate when none are outdated', function () {
        $repo = stubDepStatsRepo([
            'total' => 30,
            'outdated' => 0,
            'totalVulnerabilities' => 0,
        ]);
        $handler = new GetDependencyStatsHandler($repo);

        $result = $handler(new GetDependencyStatsQuery());

        expect($result->upToDate)->toBe(30);
        expect($result->outdated)->toBe(0);
    });

    it('passes filters from query excluding null and empty', function () {
        $receivedFilters = null;
        $repo = stubDepStatsRepo(
            ['total' => 0, 'outdated' => 0, 'totalVulnerabilities' => 0],
            $receivedFilters,
        );
        $handler = new GetDependencyStatsHandler($repo);

        $handler(new GetDependencyStatsQuery(
            projectId: 'proj-123',
            packageManager: 'npm',
            type: 'runtime',
        ));

        expect($receivedFilters)->toBe([
            'projectId' => 'proj-123',
            'packageManager' => 'npm',
            'type' => 'runtime',
        ]);
    });

    it('excludes null filters', function () {
        $receivedFilters = null;
        $repo = stubDepStatsRepo(
            ['total' => 0, 'outdated' => 0, 'totalVulnerabilities' => 0],
            $receivedFilters,
        );
        $handler = new GetDependencyStatsHandler($repo);

        $handler(new GetDependencyStatsQuery(
            projectId: null,
            packageManager: null,
            type: null,
        ));

        expect($receivedFilters)->toBeEmpty();
    });

    it('excludes empty string filters', function () {
        $receivedFilters = null;
        $repo = stubDepStatsRepo(
            ['total' => 0, 'outdated' => 0, 'totalVulnerabilities' => 0],
            $receivedFilters,
        );
        $handler = new GetDependencyStatsHandler($repo);

        $handler(new GetDependencyStatsQuery(
            projectId: '',
            packageManager: '',
            type: '',
        ));

        expect($receivedFilters)->toBeEmpty();
    });

    it('passes partial filters when some are set', function () {
        $receivedFilters = null;
        $repo = stubDepStatsRepo(
            ['total' => 0, 'outdated' => 0, 'totalVulnerabilities' => 0],
            $receivedFilters,
        );
        $handler = new GetDependencyStatsHandler($repo);

        $handler(new GetDependencyStatsQuery(
            projectId: 'proj-456',
            packageManager: null,
            type: 'dev',
        ));

        expect($receivedFilters)->toBe([
            'projectId' => 'proj-456',
            'type' => 'dev',
        ]);
    });
});
