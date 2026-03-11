<?php

declare(strict_types=1);

namespace App\Activity\Application\EventListener;

use App\Activity\Domain\Model\SyncTask;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskType;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use App\Catalog\Domain\Event\ProjectScannedEvent;
use App\Catalog\Domain\Model\DetectedStack;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class CreateStackUpgradeTasksListener
{
    public function __construct(
        private SyncTaskRepositoryInterface $syncTaskRepository,
    ) {
    }

    public function __invoke(ProjectScannedEvent $event): void
    {
        $projectId = Uuid::fromString($event->projectId);

        foreach ($event->scanResult->stacks as $stack) {
            if (!$this->needsUpgrade($stack)) {
                continue;
            }

            $metadataKey = $stack->language . ':' . $stack->framework;
            $existing = $this->syncTaskRepository->findOpenByProjectAndTypeAndKey(
                $projectId,
                SyncTaskType::StackUpgrade,
                $metadataKey,
            );

            $severity = SyncTaskSeverity::Medium;
            $title = \sprintf('Stack upgrade: %s %s', $stack->language, $stack->framework ? '(' . $stack->framework . ')' : '');
            $description = \sprintf(
                '%s%s version %s may need an upgrade.',
                $stack->language,
                $stack->framework !== '' ? ' / ' . $stack->framework : '',
                $stack->version !== '' ? $stack->version : 'unknown',
            );
            $metadata = [
                'language' => $stack->language,
                'framework' => $stack->framework,
                'version' => $stack->version,
                'frameworkVersion' => $stack->frameworkVersion,
            ];

            if ($existing !== null) {
                $existing->updateInfo($severity, $title, $description, $metadata);
                $this->syncTaskRepository->save($existing);
                continue;
            }

            $task = SyncTask::create(
                type: SyncTaskType::StackUpgrade,
                severity: $severity,
                title: $title,
                description: $description,
                metadata: $metadata,
                projectId: $projectId,
            );
            $this->syncTaskRepository->save($task);
        }
    }

    private function needsUpgrade(DetectedStack $stack): bool
    {
        if ($stack->version === '') {
            return false;
        }

        $majorVersion = (int) \explode('.', $stack->version)[0];
        $knownLatest = $this->getKnownLatestMajor($stack->language);

        if ($knownLatest === null) {
            return false;
        }

        return $majorVersion < $knownLatest;
    }

    private function getKnownLatestMajor(string $language): ?int
    {
        return match (\strtolower($language)) {
            'php' => 8,
            'python' => 3,
            'ruby' => 3,
            'go' => 1,
            'rust' => 1,
            'java' => 21,
            'node', 'nodejs' => 22,
            'typescript' => 5,
            default => null,
        };
    }
}
