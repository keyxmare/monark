<?php

declare(strict_types=1);

use App\Shared\Domain\ValueObject\PackageManager;
use App\VersionRegistry\Domain\Model\Product;
use App\VersionRegistry\Domain\Model\ProductType;
use App\VersionRegistry\Domain\Model\ResolverSource;

describe('Product', function () {
    it('creates a product with factory method', function () {
        $product = Product::create(
            name: 'symfony',
            type: ProductType::Framework,
            resolverSource: ResolverSource::EndOfLife,
        );

        expect($product->getName())->toBe('symfony');
        expect($product->getType())->toBe(ProductType::Framework);
        expect($product->getResolverSource())->toBe(ResolverSource::EndOfLife);
        expect($product->getPackageManager())->toBeNull();
        expect($product->getLatestVersion())->toBeNull();
        expect($product->getLtsVersion())->toBeNull();
        expect($product->getLastSyncedAt())->toBeNull();
    });

    it('creates a package product with package manager', function () {
        $product = Product::create(
            name: 'vue',
            type: ProductType::Package,
            resolverSource: ResolverSource::Registry,
            packageManager: PackageManager::Npm,
        );

        expect($product->getPackageManager())->toBe(PackageManager::Npm);
    });

    it('updates sync result', function () {
        $product = Product::create(
            name: 'php',
            type: ProductType::Language,
            resolverSource: ResolverSource::EndOfLife,
        );

        $product->updateSyncResult('8.4.2', '8.4.2');

        expect($product->getLatestVersion())->toBe('8.4.2');
        expect($product->getLtsVersion())->toBe('8.4.2');
        expect($product->getLastSyncedAt())->not->toBeNull();
    });
});
