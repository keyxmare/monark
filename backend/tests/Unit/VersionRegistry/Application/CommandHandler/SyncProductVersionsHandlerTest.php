<?php

declare(strict_types=1);

use App\Shared\Domain\ValueObject\PackageManager;
use App\VersionRegistry\Application\Command\SyncProductVersionsCommand;
use App\VersionRegistry\Application\Command\SyncSingleProductCommand;
use App\VersionRegistry\Application\CommandHandler\SyncProductVersionsHandler;
use App\VersionRegistry\Domain\Model\Product;
use App\VersionRegistry\Domain\Model\ProductType;
use App\VersionRegistry\Domain\Model\ResolverSource;
use App\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

function stubSyncProductVersionsHandlerRepo(array $all, array $byNames = []): ProductRepositoryInterface
{
    return new class ($all, $byNames) implements ProductRepositoryInterface {
        public function __construct(private readonly array $all, private readonly array $byNames)
        {
        }

        public function findAll(): array
        {
            return $this->all;
        }

        public function findByNames(array $names): array
        {
            return $this->byNames;
        }

        public function findByNameAndManager(string $name, ?PackageManager $packageManager): ?Product
        {
            return null;
        }

        public function findStale(\DateTimeImmutable $before): array
        {
            return [];
        }

        public function save(Product $product): void
        {
        }
    };
}

function stubSyncProductVersionsHandlerBus(): MessageBusInterface
{
    return new class () implements MessageBusInterface {
        /** @var list<object> */
        public array $dispatched = [];

        public function dispatch(object $message, array $stamps = []): Envelope
        {
            $this->dispatched[] = $message;

            return new Envelope($message);
        }
    };
}

describe('SyncProductVersionsHandler', function (): void {
    it('dispatches a SyncSingleProductCommand per product returned by findAll', function (): void {
        $products = [
            Product::create('symfony', ProductType::Framework, ResolverSource::Registry, PackageManager::Composer),
            Product::create('react', ProductType::Framework, ResolverSource::Registry, PackageManager::Npm),
        ];
        $repo = \stubSyncProductVersionsHandlerRepo($products);
        $bus = \stubSyncProductVersionsHandlerBus();
        $handler = new SyncProductVersionsHandler($repo, $bus);

        $count = $handler(new SyncProductVersionsCommand());

        expect($count)->toBe(2);
        expect($bus->dispatched)->toHaveCount(2);
        expect($bus->dispatched[0])->toBeInstanceOf(SyncSingleProductCommand::class);
        /** @var SyncSingleProductCommand $first */
        $first = $bus->dispatched[0];
        expect($first->productName)->toBe('symfony');
        expect($first->index)->toBe(1);
        expect($first->total)->toBe(2);
    });

    it('uses findByNames when productNames filter is provided', function (): void {
        $filtered = [
            Product::create('laravel', ProductType::Framework, ResolverSource::Registry, PackageManager::Composer),
        ];
        $repo = \stubSyncProductVersionsHandlerRepo([], $filtered);
        $bus = \stubSyncProductVersionsHandlerBus();
        $handler = new SyncProductVersionsHandler($repo, $bus);

        $count = $handler(new SyncProductVersionsCommand(productNames: ['laravel']));

        expect($count)->toBe(1);
        expect($bus->dispatched)->toHaveCount(1);
        /** @var SyncSingleProductCommand $cmd */
        $cmd = $bus->dispatched[0];
        expect($cmd->productName)->toBe('laravel');
    });

    it('reuses the provided syncId when given', function (): void {
        $syncId = Uuid::v7()->toRfc4122();
        $products = [Product::create('vue', ProductType::Framework, ResolverSource::Registry, PackageManager::Npm)];
        $repo = \stubSyncProductVersionsHandlerRepo($products);
        $bus = \stubSyncProductVersionsHandlerBus();
        $handler = new SyncProductVersionsHandler($repo, $bus);

        $handler(new SyncProductVersionsCommand(syncId: $syncId));

        /** @var SyncSingleProductCommand $cmd */
        $cmd = $bus->dispatched[0];
        expect($cmd->syncId)->toBe($syncId);
    });

    it('returns 0 and dispatches nothing when no products match', function (): void {
        $repo = \stubSyncProductVersionsHandlerRepo([]);
        $bus = \stubSyncProductVersionsHandlerBus();
        $handler = new SyncProductVersionsHandler($repo, $bus);

        $count = $handler(new SyncProductVersionsCommand());

        expect($count)->toBe(0);
        expect($bus->dispatched)->toHaveCount(0);
    });
});
