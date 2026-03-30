<?php

declare(strict_types=1);

use App\Catalog\Application\EventListener\UpdateTechStackVersionStatusListener;
use App\Catalog\Application\Service\TechStackVersionStatusUpdater;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Model\TechStack;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use App\Shared\Domain\Event\ProductVersionsSyncedEvent;
use App\Shared\Domain\ValueObject\PackageManager;
use App\VersionRegistry\Domain\Model\ProductVersion;
use App\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use App\VersionRegistry\Domain\Repository\ProductVersionRepositoryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

function makeTechStack(string $language, string $framework, string $version, string $frameworkVersion): TechStack
{
    $project = Project::create(
        name: 'Test Project',
        slug: 'test-project-' . \uniqid(),
        description: null,
        repositoryUrl: 'https://github.com/test/test',
        defaultBranch: 'main',
        visibility: ProjectVisibility::Public,
        ownerId: Uuid::v7(),
    );

    return TechStack::create(
        language: $language,
        framework: $framework,
        version: $version,
        frameworkVersion: $frameworkVersion,
        detectedAt: new \DateTimeImmutable(),
        project: $project,
    );
}

function makeUpdaterForListener(TechStackRepositoryInterface $techStackRepo): TechStackVersionStatusUpdater
{
    $productRepo = new class () implements ProductRepositoryInterface {
        public function findByNameAndManager(string $name, ?\App\Shared\Domain\ValueObject\PackageManager $pm): ?\App\VersionRegistry\Domain\Model\Product
        {
            return null;
        }
        public function findAll(): array
        {
            return [];
        }
        public function findStale(\DateTimeImmutable $before): array
        {
            return [];
        }
        public function findByNames(array $names): array
        {
            return [];
        }
        public function save(\App\VersionRegistry\Domain\Model\Product $product): void
        {
        }
    };

    $versionRepo = new class () implements ProductVersionRepositoryInterface {
        public function findByNameAndManager(string $name, ?\App\Shared\Domain\ValueObject\PackageManager $pm): array
        {
            return [];
        }
        public function findLatestByNameAndManager(string $name, ?\App\Shared\Domain\ValueObject\PackageManager $pm): ?ProductVersion
        {
            return null;
        }
        public function findByNameManagerAndVersion(string $name, ?\App\Shared\Domain\ValueObject\PackageManager $pm, string $version): ?ProductVersion
        {
            return null;
        }
        public function save(ProductVersion $pv): void
        {
        }
        public function clearLatestFlag(string $name, ?\App\Shared\Domain\ValueObject\PackageManager $pm): void
        {
        }
    };

    $eventBus = new class () implements MessageBusInterface {
        public function dispatch(object $message, array $stamps = []): Envelope
        {
            return new Envelope($message);
        }
    };

    return new TechStackVersionStatusUpdater($techStackRepo, $versionRepo, $productRepo, $eventBus);
}

describe('UpdateTechStackVersionStatusListener', function () {
    it('skips events that have a packageManager (those belong to dependency listener)', function () {
        $repo = $this->createMock(TechStackRepositoryInterface::class);
        $repo->expects($this->never())->method('findByFramework');
        $repo->expects($this->never())->method('findByLanguage');

        $event = new ProductVersionsSyncedEvent(
            productName: 'symfony/symfony',
            packageManager: PackageManager::Composer,
            latestVersion: '7.2.0',
            ltsVersion: null,
        );

        $listener = new UpdateTechStackVersionStatusListener($repo, \makeUpdaterForListener($repo));
        $listener($event);
    });

    it('calls findByFramework for a known framework', function () {
        $ts = \makeTechStack('PHP', 'Symfony', '8.2.0', '6.4.0');

        $repo = $this->createMock(TechStackRepositoryInterface::class);
        $repo->expects($this->once())->method('findByFramework')->with('Symfony')->willReturn([$ts]);
        $repo->method('findByLanguage')->willReturn([]);

        $event = new ProductVersionsSyncedEvent(
            productName: 'symfony',
            packageManager: null,
            latestVersion: '7.2.0',
            ltsVersion: '7.1.0',
            eolCycles: [],
        );

        $listener = new UpdateTechStackVersionStatusListener($repo, \makeUpdaterForListener($repo));
        $listener($event);
    });

    it('calls findByLanguage for a known language', function () {
        $ts = \makeTechStack('PHP', 'Symfony', '8.1.0', '6.0.0');

        $repo = $this->createMock(TechStackRepositoryInterface::class);
        $repo->expects($this->once())->method('findByLanguage')->with('PHP')->willReturn([$ts]);
        $repo->method('findByFramework')->willReturn([]);

        $event = new ProductVersionsSyncedEvent(
            productName: 'php',
            packageManager: null,
            latestVersion: '8.3.0',
            ltsVersion: null,
            eolCycles: [],
        );

        $listener = new UpdateTechStackVersionStatusListener($repo, \makeUpdaterForListener($repo));
        $listener($event);
    });

    it('skips unknown products without calling repo methods', function () {
        $repo = $this->createMock(TechStackRepositoryInterface::class);
        $repo->expects($this->never())->method('findByFramework');
        $repo->expects($this->never())->method('findByLanguage');

        $event = new ProductVersionsSyncedEvent(
            productName: 'unknown-product',
            packageManager: null,
            latestVersion: '1.0.0',
            ltsVersion: null,
        );

        $listener = new UpdateTechStackVersionStatusListener($repo, \makeUpdaterForListener($repo));
        $listener($event);
    });

    it('handles nodejs mapping to multiple language names', function () {
        $ts1 = \makeTechStack('JavaScript', 'React', '16.0.0', '18.0.0');
        $ts2 = \makeTechStack('Node.js', 'Express', '18.0.0', '4.18.0');

        $repo = $this->createMock(TechStackRepositoryInterface::class);
        $repo->method('findByLanguage')->willReturnMap([
            ['JavaScript', [$ts1]],
            ['TypeScript', []],
            ['Node.js', [$ts2]],
        ]);

        $event = new ProductVersionsSyncedEvent(
            productName: 'nodejs',
            packageManager: null,
            latestVersion: '22.0.0',
            ltsVersion: '20.11.0',
        );

        $listener = new UpdateTechStackVersionStatusListener($repo, \makeUpdaterForListener($repo));
        $listener($event);
    });
});
