<?php

declare(strict_types=1);

use App\Catalog\Application\EventListener\RefreshTechStackStatusOnScanListener;
use App\Catalog\Application\Service\FrameworkVersionStatusUpdater;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Shared\Domain\DTO\ScanResult;
use App\Shared\Domain\Event\ProjectScannedEvent;
use App\VersionRegistry\Domain\Model\ProductVersion;
use App\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use App\VersionRegistry\Domain\Repository\ProductVersionRepositoryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\FrameworkFactory;

function makeScanListenerUpdater(FrameworkRepositoryInterface $fwRepo): FrameworkVersionStatusUpdater
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

    return new FrameworkVersionStatusUpdater($fwRepo, $versionRepo, $productRepo, $eventBus);
}

describe('RefreshTechStackStatusOnScanListener', function () {
    it('calls findByProjectId and refreshAll with the found frameworks', function () {
        $fw = FrameworkFactory::create(name: 'Symfony', version: '7.1.0');
        $projectId = Uuid::v7();

        $repo = $this->createMock(FrameworkRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('findByProjectId')
            ->with($this->callback(fn ($id) => $id->toRfc4122() === $projectId->toRfc4122()))
            ->willReturn([$fw]);

        $event = new ProjectScannedEvent(
            projectId: $projectId->toRfc4122(),
            scanResult: new ScanResult(stacks: [], dependencies: []),
        );

        $listener = new RefreshTechStackStatusOnScanListener($repo, \makeScanListenerUpdater($repo));
        $listener($event);
    });

    it('does nothing when no frameworks found for project', function () {
        $projectId = Uuid::v7();

        $repo = $this->createMock(FrameworkRepositoryInterface::class);
        $repo->expects($this->once())->method('findByProjectId')->willReturn([]);
        $repo->expects($this->never())->method('save');

        $event = new ProjectScannedEvent(
            projectId: $projectId->toRfc4122(),
            scanResult: new ScanResult(stacks: [], dependencies: []),
        );

        $listener = new RefreshTechStackStatusOnScanListener($repo, \makeScanListenerUpdater($repo));
        $listener($event);
    });
});
