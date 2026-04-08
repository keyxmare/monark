<?php

declare(strict_types=1);

namespace App\Catalog\Application\EventListener;

use App\Catalog\Application\Service\FrameworkVersionStatusUpdater;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Shared\Domain\Event\ProjectScannedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class RefreshTechStackStatusOnScanListener
{
    public function __construct(
        private FrameworkRepositoryInterface $frameworkRepository,
        private FrameworkVersionStatusUpdater $updater,
    ) {
    }

    public function __invoke(ProjectScannedEvent $event): void
    {
        $frameworks = $this->frameworkRepository->findByProjectId(
            Uuid::fromString($event->projectId),
        );

        $this->updater->refreshAll($frameworks);
    }
}
