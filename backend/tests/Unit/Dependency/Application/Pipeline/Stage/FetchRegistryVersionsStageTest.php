<?php

declare(strict_types=1);

use App\Dependency\Application\Pipeline\Stage\FetchRegistryVersionsStage;
use App\Dependency\Application\Pipeline\SyncContext;
use App\Dependency\Domain\DTO\RegistryVersion;
use App\Dependency\Domain\Port\PackageRegistryResolverPort;
use App\Shared\Domain\ValueObject\PackageManager;

function makeFetchResolver(array $versions): PackageRegistryResolverPort
{
    return new class ($versions) implements PackageRegistryResolverPort {
        public ?string $receivedSince = null;

        public function __construct(private readonly array $versions)
        {
        }

        public function fetchVersions(string $packageName, PackageManager $manager, ?string $sinceVersion = null): array
        {
            $this->receivedSince = $sinceVersion;

            return $this->versions;
        }
    };
}

describe('FetchRegistryVersionsStage', function () {
    it('populates registryVersions in context', function () {
        $resolver = \makeFetchResolver([
            new RegistryVersion('1.0.0', new \DateTimeImmutable(), false),
            new RegistryVersion('1.1.0', new \DateTimeImmutable(), true),
        ]);
        $stage = new FetchRegistryVersionsStage($resolver);
        $ctx = SyncContext::initial('vue', PackageManager::Npm);

        $result = $stage($ctx);

        expect($result->registryVersions)->toHaveCount(2);
    });

    it('passes sinceVersion from context to resolver', function () {
        $resolver = \makeFetchResolver([]);
        $stage = new FetchRegistryVersionsStage($resolver);
        $ctx = SyncContext::initial('vue', PackageManager::Npm)
            ->withLatestVersion('1.0.0');

        $stage($ctx);

        expect($resolver->receivedSince)->toBe('1.0.0');
    });

    it('passes null sinceVersion when no latest known', function () {
        $resolver = \makeFetchResolver([]);
        $stage = new FetchRegistryVersionsStage($resolver);
        $ctx = SyncContext::initial('vue', PackageManager::Npm);

        $stage($ctx);

        expect($resolver->receivedSince)->toBeNull();
    });

    it('returns empty registryVersions when registry returns nothing', function () {
        $resolver = \makeFetchResolver([]);
        $stage = new FetchRegistryVersionsStage($resolver);
        $ctx = SyncContext::initial('unknown-pkg', PackageManager::Composer);

        $result = $stage($ctx);

        expect($result->registryVersions)->toBeEmpty();
    });
});
