<?php

declare(strict_types=1);

namespace App\VersionRegistry\Application\EventListener;

use App\Shared\Domain\Event\ProjectScannedEvent;
use App\VersionRegistry\Application\Command\SyncProductVersionsCommand;
use App\VersionRegistry\Domain\Model\Product;
use App\VersionRegistry\Domain\Model\ProductType;
use App\VersionRegistry\Domain\Model\ResolverSource;
use App\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class RegisterScannedProductsListener
{
    private const array LANGUAGE_MAP = [
        'PHP' => 'php',
        'Python' => 'python',
        'JavaScript' => 'nodejs',
        'TypeScript' => 'nodejs',
        'Node.js' => 'nodejs',
        'Go' => 'go',
        'Rust' => 'rust',
        'Ruby' => 'ruby',
    ];

    private const array FRAMEWORK_MAP = [
        'Symfony' => 'symfony',
        'Laravel' => 'laravel',
        'Vue' => 'vue',
        'Nuxt' => 'nuxt',
        'Angular' => 'angular',
        'React' => 'react',
        'Next.js' => 'next.js',
        'Django' => 'django',
        'Rails' => 'rails',
    ];

    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(ProjectScannedEvent $event): void
    {
        $newProducts = [];

        foreach ($event->scanResult->stacks as $stack) {
            $langProduct = self::LANGUAGE_MAP[$stack->language] ?? null;
            if ($langProduct !== null && !isset($newProducts[$langProduct])) {
                $existing = $this->productRepository->findByNameAndManager($langProduct, null);
                if ($existing === null) {
                    $product = Product::create($langProduct, ProductType::Language, ResolverSource::EndOfLife);
                    $this->productRepository->save($product);
                    $newProducts[$langProduct] = true;
                }
            }

            if ($stack->framework !== 'none') {
                $fwProduct = self::FRAMEWORK_MAP[$stack->framework] ?? null;
                if ($fwProduct !== null && !isset($newProducts[$fwProduct])) {
                    $existing = $this->productRepository->findByNameAndManager($fwProduct, null);
                    if ($existing === null) {
                        $product = Product::create($fwProduct, ProductType::Framework, ResolverSource::EndOfLife);
                        $this->productRepository->save($product);
                        $newProducts[$fwProduct] = true;
                    }
                }
            }
        }

        foreach ($event->scanResult->dependencies as $dep) {
            $pm = $dep->packageManager;
            $key = $pm->value . ':' . $dep->name;
            if (!isset($newProducts[$key])) {
                $existing = $this->productRepository->findByNameAndManager($dep->name, $pm);
                if ($existing === null) {
                    $product = Product::create($dep->name, ProductType::Package, ResolverSource::Registry, $pm);
                    $this->productRepository->save($product);
                    $newProducts[$key] = true;
                }
            }
        }

        if ($newProducts !== []) {
            $this->commandBus->dispatch(new SyncProductVersionsCommand(
                productNames: \array_keys($newProducts),
            ));
        }
    }
}
