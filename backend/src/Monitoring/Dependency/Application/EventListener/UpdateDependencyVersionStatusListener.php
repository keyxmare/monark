<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Application\EventListener;

use App\Hub\Shared\Domain\Event\ProductVersionsSyncedEvent;
use App\Monitoring\Dependency\Domain\Model\RegistryStatus;
use App\Monitoring\Dependency\Domain\Repository\DependencyRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class UpdateDependencyVersionStatusListener
{
    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
    ) {
    }

    public function __invoke(ProductVersionsSyncedEvent $event): void
    {
        if ($event->packageManager === null || $event->latestVersion === null) {
            return;
        }

        $deps = $this->dependencyRepository->findByName($event->productName, $event->packageManager->value);

        foreach ($deps as $dep) {
            $dep->update(
                latestVersion: $event->latestVersion,
                ltsVersion: $event->ltsVersion,
                isOutdated: \version_compare($dep->getCurrentVersion(), $event->latestVersion, '<'),
            );
            $dep->markRegistryStatus(RegistryStatus::Synced);
            $this->dependencyRepository->save($dep);
        }
    }
}
