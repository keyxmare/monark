<?php

declare(strict_types=1);

namespace App\Catalog\Application\EventListener;

use App\Catalog\Application\Service\TechStackVersionStatusUpdater;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use App\Shared\Domain\Event\ProductVersionsSyncedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class UpdateTechStackVersionStatusListener
{
    private const array LANGUAGE_REVERSE_MAP = [
        'php' => 'PHP',
        'python' => 'Python',
        'nodejs' => ['JavaScript', 'TypeScript', 'Node.js'],
        'go' => 'Go',
        'rust' => 'Rust',
        'ruby' => 'Ruby',
    ];

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
        private TechStackRepositoryInterface $techStackRepository,
        private TechStackVersionStatusUpdater $updater,
    ) {
    }

    public function __invoke(ProductVersionsSyncedEvent $event): void
    {
        if ($event->packageManager !== null) {
            return;
        }

        $frameworkName = self::FRAMEWORK_REVERSE_MAP[$event->productName] ?? null;
        $languageNames = self::LANGUAGE_REVERSE_MAP[$event->productName] ?? null;

        $techStacks = [];

        if ($frameworkName !== null) {
            $techStacks = $this->techStackRepository->findByFramework($frameworkName);
        } elseif ($languageNames !== null) {
            $names = \is_array($languageNames) ? $languageNames : [$languageNames];
            foreach ($names as $name) {
                \array_push($techStacks, ...$this->techStackRepository->findByLanguage($name));
            }
        }

        $this->updater->refreshAll($techStacks);
    }
}
