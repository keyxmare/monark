<?php

declare(strict_types=1);

namespace App\Dependency\Application\EventListener;

use App\Dependency\Domain\Model\RegistryStatus;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\Event\ProductVersionsSyncedEvent;
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
