<?php

declare(strict_types=1);

use App\Catalog\Application\Service\TechStackVersionStatusUpdater;
use App\Catalog\Domain\Event\TechStackVersionStatusUpdated;
use App\Catalog\Domain\Model\TechStack;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use App\VersionRegistry\Domain\Model\Product;
use App\VersionRegistry\Domain\Model\ProductVersion;
use App\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use App\VersionRegistry\Domain\Repository\ProductVersionRepositoryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProjectFactory;

function createTechStackRepo(): TechStackRepositoryInterface
{
    return new class () implements TechStackRepositoryInterface {
        public ?TechStack $saved = null;

        public function findById(Uuid $id): ?TechStack
        {
            return null;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function countByProjectId(Uuid $projectId): int
        {
            return 0;
        }
        public function count(): int
        {
            return 0;
        }
        public function save(TechStack $ts): void
        {
            $this->saved = $ts;
        }
        public function delete(TechStack $ts): void
        {
        }
        public function deleteByProjectId(Uuid $projectId): void
        {
        }
        public function findByFramework(string $framework): array
        {
            return [];
        }
        public function findByLanguage(string $language): array
        {
            return [];
        }
    };
}

function createEmptyProductRepo(): ProductRepositoryInterface
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

function createEmptyVersionRepo(): ProductVersionRepositoryInterface
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
    };
}

function createEventBus(array &$dispatched): MessageBusInterface
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

describe('TechStackVersionStatusUpdater', function () {
    it('does not dispatch events when framework is not in the map', function () {
        $dispatched = [];

        $updater = new TechStackVersionStatusUpdater(
            \createTechStackRepo(),
            \createEmptyVersionRepo(),
            \createEmptyProductRepo(),
            \createEventBus($dispatched),
        );

        $project = ProjectFactory::create();
        $stack = TechStack::create('JavaScript', 'UnknownFramework', '3.0.0', '3.0.0', new \DateTimeImmutable(), $project);

        $updated = $updater->refreshAll([$stack]);

        expect($updated)->toBe(0)
            ->and($dispatched)->toBeEmpty();
    });

    it('dispatches TechStackVersionStatusUpdated after refreshing a known framework with no versions', function () {
        $dispatched = [];

        $updater = new TechStackVersionStatusUpdater(
            \createTechStackRepo(),
            \createEmptyVersionRepo(),
            \createEmptyProductRepo(),
            \createEventBus($dispatched),
        );

        $project = ProjectFactory::create();
        $stack = TechStack::create('JavaScript', 'Vue', '3.0.0', '3.0.0', new \DateTimeImmutable(), $project);

        $updated = $updater->refreshAll([$stack]);

        expect($updated)->toBe(0)
            ->and($dispatched)->toBeEmpty();
    });

    it('dispatches TechStackVersionStatusUpdated after saving an updated stack', function () {
        $dispatched = [];
        $techStackRepo = \createTechStackRepo();

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
        };

        $updater = new TechStackVersionStatusUpdater(
            $techStackRepo,
            $versionRepo,
            $productRepo,
            \createEventBus($dispatched),
        );

        $project = ProjectFactory::create();
        $stack = TechStack::create('PHP', 'Symfony', '8.0.0', '7.1.0', new \DateTimeImmutable(), $project);

        $updated = $updater->refreshAll([$stack]);

        expect($updated)->toBe(1);
        expect($dispatched)->toHaveCount(1);
        expect($dispatched[0])->toBeInstanceOf(TechStackVersionStatusUpdated::class);
        expect($dispatched[0]->framework)->toBe('Symfony');
    });
});
