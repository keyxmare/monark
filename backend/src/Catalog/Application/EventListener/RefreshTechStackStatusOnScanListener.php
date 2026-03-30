<?php

declare(strict_types=1);

namespace App\Catalog\Application\EventListener;

use App\Catalog\Application\Service\TechStackVersionStatusUpdater;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use App\Shared\Domain\Event\ProjectScannedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class RefreshTechStackStatusOnScanListener
{
    public function __construct(
        private TechStackRepositoryInterface $techStackRepository,
        private TechStackVersionStatusUpdater $updater,
    ) {
    }

    public function __invoke(ProjectScannedEvent $event): void
    {
        $stacks = $this->techStackRepository->findByProjectId(
            Uuid::fromString($event->projectId),
            1,
            1000,
        );

        $this->updater->refreshAll($stacks);
    }
}
