<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Application\CommandHandler;

use App\Hub\Shared\Domain\DTO\OsvQuery;
use App\Hub\Shared\Domain\Port\OsvClientInterface;
use App\Monitoring\Dependency\Application\Command\SyncDependencyCveCommand;
use App\Monitoring\Dependency\Domain\Event\DependencyCveSyncedEvent;
use App\Monitoring\Dependency\Domain\Repository\DependencyRepositoryInterface;
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
            $this->eventBus->dispatch(new DependencyCveSyncedEvent($command->projectId, 0, 0));
            return;
        }

        $queries = [];
        $indexMap = [];
        $queryIndex = 0;
        foreach ($dependencies as $depIndex => $dep) {
            $ecosystem = self::ECOSYSTEM_MAP[$dep->getPackageManager()->value];
            $queries[] = new OsvQuery($ecosystem, $dep->getName(), $dep->getCurrentVersion());
            $indexMap[$queryIndex] = $depIndex;
            ++$queryIndex;
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
                $title = \trim($osvVuln->summary) !== '' ? $osvVuln->summary : $cveId;
                $description = \trim($osvVuln->summary) !== '' ? $osvVuln->summary : $cveId;
                $dep->reportVulnerability(
                    cveId: $cveId,
                    severity: $osvVuln->severity,
                    title: $title,
                    description: $description,
                    patchedVersion: $osvVuln->patchedVersion ?? '',
                );
                ++$totalFound;
            }

            $this->dependencyRepository->save($dep);
        }

        $this->eventBus->dispatch(new DependencyCveSyncedEvent(
            $command->projectId,
            \count($dependencies),
            $totalFound,
        ));
    }
}
