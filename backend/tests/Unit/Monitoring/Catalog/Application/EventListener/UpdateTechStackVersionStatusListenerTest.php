<?php

declare(strict_types=1);

use App\Hub\Shared\Domain\Event\ProductVersionsSyncedEvent;
use App\Hub\Shared\Domain\ValueObject\PackageManager;
use App\Monitoring\Catalog\Application\EventListener\UpdateTechStackVersionStatusListener;
use App\Monitoring\Catalog\Application\Service\FrameworkVersionStatusUpdater;
use App\Monitoring\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Monitoring\VersionRegistry\Domain\Model\ProductVersion;
use App\Monitoring\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use App\Monitoring\VersionRegistry\Domain\Repository\ProductVersionRepositoryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Tests\Factory\Monitoring\Catalog\FrameworkFactory;

function makeFrameworkUpdaterForListener(FrameworkRepositoryInterface $fwRepo): FrameworkVersionStatusUpdater
{
    $productRepo = new class () implements ProductRepositoryInterface {
        public function findByNameAndManager(string $name, ?\App\Hub\Shared\Domain\ValueObject\PackageManager $pm): ?\App\Monitoring\VersionRegistry\Domain\Model\Product
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
        public function save(\App\Monitoring\VersionRegistry\Domain\Model\Product $product): void
        {
        }

        public function findByResolverSource(\App\Monitoring\VersionRegistry\Domain\Model\ResolverSource $source): array
        {
            return [];
        }
    };

    $versionRepo = new class () implements ProductVersionRepositoryInterface {
        public function findByNameAndManager(string $name, ?\App\Hub\Shared\Domain\ValueObject\PackageManager $pm): array
        {
            return [];
        }
        public function findLatestByNameAndManager(string $name, ?\App\Hub\Shared\Domain\ValueObject\PackageManager $pm): ?ProductVersion
        {
            return null;
        }
        public function findByNameManagerAndVersion(string $name, ?\App\Hub\Shared\Domain\ValueObject\PackageManager $pm, string $version): ?ProductVersion
        {
            return null;
        }
        public function save(ProductVersion $pv): void
        {
        }
        public function clearLatestFlag(string $name, ?\App\Hub\Shared\Domain\ValueObject\PackageManager $pm): void
        {
        }
        public function persist(ProductVersion $pv): void
        {
        }
        public function flush(): void
        {
        }
    };

    $eventBus = new class () implements MessageBusInterface {
        public function dispatch(object $message, array $stamps = []): Envelope
        {
            return new Envelope($message);
        }
    };

    $httpClient = new class () implements \Symfony\Contracts\HttpClient\HttpClientInterface {
        public function request(string $method, string $url, array $options = []): \Symfony\Contracts\HttpClient\ResponseInterface
        {
            throw new \RuntimeException('not called in tests');
        }
        public function stream(\Symfony\Contracts\HttpClient\ResponseInterface|iterable $responses, ?float $timeout = null): \Symfony\Contracts\HttpClient\ResponseStreamInterface
        {
            throw new \RuntimeException('not called in tests');
        }
        public function withOptions(array $options): static
        {
            return $this;
        }
    };

    return new FrameworkVersionStatusUpdater($fwRepo, $versionRepo, $productRepo, $eventBus, $httpClient);
}

describe('UpdateTechStackVersionStatusListener', function () {
    it('skips events that have a packageManager (those belong to dependency listener)', function () {
        $repo = $this->createMock(FrameworkRepositoryInterface::class);
        $repo->expects($this->never())->method('findByName');

        $event = new ProductVersionsSyncedEvent(
            productName: 'symfony/symfony',
            packageManager: PackageManager::Composer,
            latestVersion: '7.2.0',
            ltsVersion: null,
        );

        $listener = new UpdateTechStackVersionStatusListener($repo, \makeFrameworkUpdaterForListener($repo));
        $listener($event);
    });

    it('calls findByName for a known framework', function () {
        $fw = FrameworkFactory::create(name: 'Symfony', version: '6.4.0');

        $repo = $this->createMock(FrameworkRepositoryInterface::class);
        $repo->expects($this->once())->method('findByName')->with('Symfony')->willReturn([$fw]);

        $event = new ProductVersionsSyncedEvent(
            productName: 'symfony',
            packageManager: null,
            latestVersion: '7.2.0',
            ltsVersion: '7.1.0',
            eolCycles: [],
        );

        $listener = new UpdateTechStackVersionStatusListener($repo, \makeFrameworkUpdaterForListener($repo));
        $listener($event);
    });

    it('skips unknown products without calling repo methods', function () {
        $repo = $this->createMock(FrameworkRepositoryInterface::class);
        $repo->expects($this->never())->method('findByName');

        $event = new ProductVersionsSyncedEvent(
            productName: 'unknown-product',
            packageManager: null,
            latestVersion: '1.0.0',
            ltsVersion: null,
        );

        $listener = new UpdateTechStackVersionStatusListener($repo, \makeFrameworkUpdaterForListener($repo));
        $listener($event);
    });

    it('skips language products that are not frameworks', function () {
        $repo = $this->createMock(FrameworkRepositoryInterface::class);
        $repo->expects($this->never())->method('findByName');

        $event = new ProductVersionsSyncedEvent(
            productName: 'php',
            packageManager: null,
            latestVersion: '8.3.0',
            ltsVersion: null,
            eolCycles: [],
        );

        $listener = new UpdateTechStackVersionStatusListener($repo, \makeFrameworkUpdaterForListener($repo));
        $listener($event);
    });
});
