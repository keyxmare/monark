<?php

declare(strict_types=1);

namespace App\Catalog\Application\EventListener;

use App\Catalog\Domain\Event\ProjectSyncCompletedEvent;
use App\Catalog\Domain\Repository\SyncJobRepositoryInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class IncrementSyncJobProgressListener
{
    public function __construct(
        private SyncJobRepositoryInterface $syncJobRepository,
        private HubInterface $mercureHub,
    ) {
    }

    public function __invoke(ProjectSyncCompletedEvent $event): void
    {
        $syncJob = $this->syncJobRepository->findById(Uuid::fromString($event->syncJobId));
        if ($syncJob === null) {
            return;
        }

        $syncJob->incrementCompleted();
        $this->syncJobRepository->save($syncJob);

        $this->mercureHub->publish(new Update(
            \sprintf('/sync-jobs/%s', $event->syncJobId),
            (string) \json_encode([
                'id' => $event->syncJobId,
                'completedProjects' => $syncJob->getCompletedProjects(),
                'totalProjects' => $syncJob->getTotalProjects(),
                'status' => $syncJob->getStatus()->value,
            ]),
        ));
    }
}
