<?php

declare(strict_types=1);

use App\Catalog\Application\Service\FrameworkVersionStatusUpdater;
use App\Catalog\Domain\Event\FrameworkVersionStatusUpdated;
use App\Catalog\Domain\Model\Framework;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\VersionRegistry\Domain\Model\Product;
use App\VersionRegistry\Domain\Model\ProductVersion;
use App\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use App\VersionRegistry\Domain\Repository\ProductVersionRepositoryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProjectFactory;

function createFrameworkRepo(): FrameworkRepositoryInterface
{
    return new class () implements FrameworkRepositoryInterface {
        public ?Framework $saved = null;

        public function findById(Uuid $id): ?Framework
        {
            return null;
        }
        public function findAll(): array
        {
            return [];
        }
        public function findByProjectId(Uuid $projectId): array
        {
            return [];
        }
        public function findByLanguageId(Uuid $languageId): array
        {
            return [];
        }
        public function findByNameAndProjectId(string $name, Uuid $projectId): ?Framework
        {
            return null;
        }
        public function findByName(string $name): array
        {
            return [];
        }
        public function save(Framework $framework): void
        {
            $this->saved = $framework;
        }
        public function delete(Framework $framework): void
        {
        }
        public function deleteByProjectId(Uuid $projectId): void
        {
        }
    };
}

function createFwEmptyProductRepo(): ProductRepositoryInterface
{
    return new class () implements ProductRepositoryInterface {
        public function findByNameAndManager(string $name, ?\App\Shared\Domain\ValueObject\PackageManager $pm): ?Product
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
        public function save(Product $product): void
        {
        }
    };
}

function createFwEmptyVersionRepo(): ProductVersionRepositoryInterface
{
    return new class () implements ProductVersionRepositoryInterface {
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
        public function persist(ProductVersion $pv): void
        {
        }
        public function flush(): void
        {
        }
    };
}

function createFwEventBus(array &$dispatched): MessageBusInterface
{
    return new class ($dispatched) implements MessageBusInterface {
        public function __construct(private array &$dispatched)
        {
        }

        public function dispatch(object $message, array $stamps = []): Envelope
        {
            $this->dispatched[] = $message;
            return new Envelope($message);
        }
    };
}

describe('FrameworkVersionStatusUpdater', function () {
    it('returns 0 when framework is not in the map', function () {
        $dispatched = [];

        $updater = new FrameworkVersionStatusUpdater(
            \createFrameworkRepo(),
            \createFwEmptyVersionRepo(),
            \createFwEmptyProductRepo(),
            \createFwEventBus($dispatched),
        );

        $project = ProjectFactory::create();
        $fw = Framework::create('UnknownFramework', '1.0.0', new \DateTimeImmutable(), 'PHP', '8.4', $project);

        expect($updater->refreshAll([$fw]))->toBe(0)
            ->and($dispatched)->toBeEmpty();
    });

    it('returns 0 when no versions are available for the framework', function () {
        $dispatched = [];

        $updater = new FrameworkVersionStatusUpdater(
            \createFrameworkRepo(),
            \createFwEmptyVersionRepo(),
            \createFwEmptyProductRepo(),
            \createFwEventBus($dispatched),
        );

        $project = ProjectFactory::create();
        $fw = Framework::create('Vue', '3.5.0', new \DateTimeImmutable(), 'TypeScript', '', $project);

        expect($updater->refreshAll([$fw]))->toBe(0)
            ->and($dispatched)->toBeEmpty();
    });

    it('saves and dispatches event after refreshing a known framework with versions', function () {
        $dispatched = [];
        $fwRepo = \createFrameworkRepo();

        $productRepo = new class () implements ProductRepositoryInterface {
            public function findByNameAndManager(string $name, ?\App\Shared\Domain\ValueObject\PackageManager $pm): ?Product
            {
                return Product::create($name, \App\VersionRegistry\Domain\Model\ProductType::Framework, \App\VersionRegistry\Domain\Model\ResolverSource::EndOfLife, null);
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
            public function save(Product $product): void
            {
            }
        };

        $versionRepo = new class () implements ProductVersionRepositoryInterface {
            public function findByNameAndManager(string $name, ?\App\Shared\Domain\ValueObject\PackageManager $pm): array
            {
                $pv = ProductVersion::create($name, '7.2.0', null, null, false, false, '2027-01-01');
                return [$pv];
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
            public function persist(ProductVersion $pv): void
            {
            }
            public function flush(): void
            {
            }
        };

        $updater = new FrameworkVersionStatusUpdater(
            $fwRepo,
            $versionRepo,
            $productRepo,
            \createFwEventBus($dispatched),
        );

        $project = ProjectFactory::create();
        $fw = Framework::create('Symfony', '7.1.0', new \DateTimeImmutable(), 'PHP', '8.4', $project);

        $updated = $updater->refreshAll([$fw]);

        expect($updated)->toBe(1);
        expect($fwRepo->saved)->toBeInstanceOf(Framework::class);
        expect($dispatched)->toHaveCount(1);
        expect($dispatched[0])->toBeInstanceOf(FrameworkVersionStatusUpdated::class);
        expect($dispatched[0]->framework)->toBe('Symfony');
    });
});
