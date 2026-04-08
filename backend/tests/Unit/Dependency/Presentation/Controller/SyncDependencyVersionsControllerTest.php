<?php

declare(strict_types=1);

use App\Dependency\Application\Command\SyncDependencyVersionsCommand;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Dependency\Presentation\Controller\SyncDependencyVersionsController;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

describe('SyncDependencyVersionsController', function () {
    it('dispatches sync command and returns 202', function () {
        $bus = new class () extends stdClass implements MessageBusInterface {
            public ?object $dispatched = null;
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                $this->dispatched = $message;
                return new Envelope($message);
            }
        };

        $depRepo = new class () implements DependencyRepositoryInterface {
            public function findById(Uuid $id): ?\App\Dependency\Domain\Model\Dependency
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
            public function save(\App\Dependency\Domain\Model\Dependency $dependency): void
            {
            }
            public function delete(\App\Dependency\Domain\Model\Dependency $dependency): void
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
            public function findUniquePackages(): array
            {
                return [];
            }
            public function findByName(string $name, string $packageManager): array
            {
                return [];
            }
            public function findByNameManagerAndProjectId(string $name, string $packageManager, Uuid $projectId): ?\App\Dependency\Domain\Model\Dependency
            {
                return null;
            }
            public function getStats(array $filters = []): array
            {
                return ['total' => 0, 'outdated' => 0, 'totalVulnerabilities' => 0];
            }
            public function getStatsSingle(array $filters = []): array
            {
                return ['total' => 0, 'outdated' => 0, 'totalVulnerabilities' => 0];
            }
            public function findFilteredWithVersionDates(int $page, int $perPage, array $filters = []): array
            {
                return [];
            }
        };

        $response = (new SyncDependencyVersionsController($bus, $depRepo))();

        expect($response->getStatusCode())->toBe(202);
        expect($bus->dispatched)->toBeInstanceOf(SyncDependencyVersionsCommand::class);
        expect($bus->dispatched->syncId)->toBeString()->not->toBeEmpty();
    });
});
