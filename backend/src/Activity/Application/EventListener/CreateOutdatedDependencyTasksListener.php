<?php

declare(strict_types=1);

namespace App\Activity\Application\EventListener;

use App\Activity\Domain\Model\SyncTask;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskType;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use App\Catalog\Domain\Event\ProjectScannedEvent;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class CreateOutdatedDependencyTasksListener
{
    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
        private SyncTaskRepositoryInterface $syncTaskRepository,
    ) {
    }

    public function __invoke(ProjectScannedEvent $event): void
    {
        $projectId = Uuid::fromString($event->projectId);
        $dependencies = $this->dependencyRepository->findByProjectId($projectId);

        foreach ($dependencies as $dependency) {
            if (!$dependency->isOutdated()) {
                continue;
            }

            $existing = $this->syncTaskRepository->findOpenByProjectAndTypeAndKey(
                $projectId,
                SyncTaskType::OutdatedDependency,
                $dependency->getName(),
            );

            $severity = $this->determineSeverity($dependency->getCurrentVersion(), $dependency->getLatestVersion());
            $title = \sprintf('Outdated dependency: %s', $dependency->getName());
            $description = \sprintf(
                '%s is at version %s, latest is %s (%s).',
                $dependency->getName(),
                $dependency->getCurrentVersion(),
                $dependency->getLatestVersion(),
                $dependency->getPackageManager()->value,
            );
            $metadata = [
                'dependencyName' => $dependency->getName(),
                'currentVersion' => $dependency->getCurrentVersion(),
                'latestVersion' => $dependency->getLatestVersion(),
                'packageManager' => $dependency->getPackageManager()->value,
            ];

            if ($existing !== null) {
                $existing->updateInfo($severity, $title, $description, $metadata);
                $this->syncTaskRepository->save($existing);
                continue;
            }

            $task = SyncTask::create(
                type: SyncTaskType::OutdatedDependency,
                severity: $severity,
                title: $title,
                description: $description,
                metadata: $metadata,
                projectId: $projectId,
            );
            $this->syncTaskRepository->save($task);
        }
    }

    private function determineSeverity(string $current, string $latest): SyncTaskSeverity
    {
        $currentParts = \explode('.', $current);
        $latestParts = \explode('.', $latest);

        $currentMajor = (int) $currentParts[0];
        $latestMajor = (int) $latestParts[0];

        if ($latestMajor - $currentMajor >= 2) {
            return SyncTaskSeverity::Critical;
        }
        if ($latestMajor > $currentMajor) {
            return SyncTaskSeverity::High;
        }

        $currentMinor = (int) ($currentParts[1] ?? 0);
        $latestMinor = (int) ($latestParts[1] ?? 0);

        if ($latestMinor - $currentMinor >= 5) {
            return SyncTaskSeverity::Medium;
        }

        return SyncTaskSeverity::Low;
    }
}
