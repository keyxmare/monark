<?php

declare(strict_types=1);

namespace App\Dependency\Application\CommandHandler;

use App\Dependency\Application\Command\SyncDependencyCveCommand;
use App\Dependency\Domain\Event\DependencyCveSyncedEvent;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\DTO\OsvQuery;
use App\Shared\Domain\Port\OsvClientInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class SyncDependencyCveHandler
{
    private const array ECOSYSTEM_MAP = [
        'composer' => 'Packagist',
        'npm' => 'npm',
        'pip' => 'PyPI',
        'poetry' => 'PyPI',
    ];

    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
        private OsvClientInterface $osvClient,
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(SyncDependencyCveCommand $command): void
    {
        $projectId = Uuid::fromString($command->projectId);
        $dependencies = $this->dependencyRepository->findByProjectId($projectId, 1, 10000);

        if ($dependencies === []) {
            $this->eventBus->dispatch(new DependencyCveSyncedEvent($command->projectId, 0));
            return;
        }

        $queries = [];
        $indexMap = [];
        $queryIndex = 0;
        foreach ($dependencies as $depIndex => $dep) {
            $ecosystem = self::ECOSYSTEM_MAP[$dep->getPackageManager()->value] ?? null;
            if ($ecosystem === null) {
                continue;
            }
            $queries[] = new OsvQuery($ecosystem, $dep->getName(), $dep->getCurrentVersion());
            $indexMap[$queryIndex] = $depIndex;
            ++$queryIndex;
        }

        if ($queries === []) {
            $this->eventBus->dispatch(new DependencyCveSyncedEvent($command->projectId, 0));
            return;
        }

        $results = $this->osvClient->queryBatch($queries);

        $totalFound = 0;
        foreach ($results as $queryIdx => $vulns) {
            $depIndex = $indexMap[$queryIdx] ?? null;
            if ($depIndex === null || !isset($dependencies[$depIndex])) {
                continue;
            }
            $dep = $dependencies[$depIndex];

            foreach ($vulns as $osvVuln) {
                $cveId = $osvVuln->cveId ?? $osvVuln->id;
                $dep->reportVulnerability(
                    cveId: $cveId,
                    severity: $osvVuln->severity,
                    title: $osvVuln->summary,
                    description: $osvVuln->summary,
                    patchedVersion: $osvVuln->patchedVersion ?? '',
                );
                ++$totalFound;
            }

            $this->dependencyRepository->save($dep);
        }

        $this->eventBus->dispatch(new DependencyCveSyncedEvent($command->projectId, $totalFound));
    }
}
