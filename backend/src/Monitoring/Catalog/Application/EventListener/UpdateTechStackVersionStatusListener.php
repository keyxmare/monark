<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\EventListener;

use App\Hub\Shared\Domain\Event\ProductVersionsSyncedEvent;
use App\Monitoring\Catalog\Application\Service\FrameworkVersionStatusUpdater;
use App\Monitoring\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class UpdateTechStackVersionStatusListener
{
    private const array FRAMEWORK_REVERSE_MAP = [
        'symfony' => 'Symfony',
        'laravel' => 'Laravel',
        'vue' => 'Vue',
        'nuxt' => 'Nuxt',
        'angular' => 'Angular',
        'react' => 'React',
        'next.js' => 'Next.js',
        'django' => 'Django',
        'rails' => 'Rails',
    ];

    public function __construct(
        private FrameworkRepositoryInterface $frameworkRepository,
        private FrameworkVersionStatusUpdater $updater,
    ) {
    }

    public function __invoke(ProductVersionsSyncedEvent $event): void
    {
        if ($event->packageManager !== null) {
            return;
        }

        $frameworkName = self::FRAMEWORK_REVERSE_MAP[$event->productName] ?? null;
        if ($frameworkName === null) {
            return;
        }

        $frameworks = $this->frameworkRepository->findByName($frameworkName);
        $this->updater->refreshAll($frameworks);
    }
}
