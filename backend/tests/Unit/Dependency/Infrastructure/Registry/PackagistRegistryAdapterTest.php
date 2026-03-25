<?php

declare(strict_types=1);

use App\Dependency\Infrastructure\Registry\PackagistRegistryAdapter;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

describe('PackagistRegistryAdapter', function () {
    it('fetches versions with release dates from packagist', function () {
        $responseData = \json_encode([
            'packages' => [
                'symfony/framework-bundle' => [
                    ['version' => 'v8.0.7', 'time' => '2026-03-06T15:40:00+00:00'],
                    ['version' => 'v8.0.6', 'time' => '2026-02-25T16:59:43+00:00'],
                    ['version' => 'v7.4.7', 'time' => '2026-02-25T16:59:43+00:00'],
                ],
            ],
        ]);

        $client = new MockHttpClient(new MockResponse($responseData));
        $adapter = new PackagistRegistryAdapter($client);

        $versions = $adapter->fetchVersions('symfony/framework-bundle', PackageManager::Composer);

        expect($versions)->toHaveCount(3);
        expect($versions[0]->version)->toBe('8.0.7');
        expect($versions[0]->isLatest)->toBeTrue();
        expect($versions[0]->releaseDate)->not->toBeNull();
        expect($versions[1]->version)->toBe('8.0.6');
        expect($versions[1]->isLatest)->toBeFalse();
    });

    it('filters dev and pre-release versions', function () {
        $responseData = \json_encode([
            'packages' => [
                'some/lib' => [
                    ['version' => 'v2.0.0', 'time' => '2026-01-01T00:00:00+00:00'],
                    ['version' => 'v2.0.0-beta1', 'time' => '2025-12-01T00:00:00+00:00'],
                    ['version' => 'dev-main', 'time' => '2026-03-01T00:00:00+00:00'],
                    ['version' => 'v1.0.0', 'time' => '2025-06-01T00:00:00+00:00'],
                ],
            ],
        ]);

        $client = new MockHttpClient(new MockResponse($responseData));
        $adapter = new PackagistRegistryAdapter($client);

        $versions = $adapter->fetchVersions('some/lib', PackageManager::Composer);

        expect($versions)->toHaveCount(2);
        expect($versions[0]->version)->toBe('2.0.0');
        expect($versions[1]->version)->toBe('1.0.0');
    });

    it('filters versions since a given version', function () {
        $responseData = \json_encode([
            'packages' => [
                'some/lib' => [
                    ['version' => 'v3.0.0', 'time' => '2026-03-01T00:00:00+00:00'],
                    ['version' => 'v2.0.0', 'time' => '2025-06-01T00:00:00+00:00'],
                    ['version' => 'v1.0.0', 'time' => '2024-01-01T00:00:00+00:00'],
                ],
            ],
        ]);

        $client = new MockHttpClient(new MockResponse($responseData));
        $adapter = new PackagistRegistryAdapter($client);

        $versions = $adapter->fetchVersions('some/lib', PackageManager::Composer, '2.0.0');

        expect($versions)->toHaveCount(1);
        expect($versions[0]->version)->toBe('3.0.0');
    });

    it('returns empty array on API error', function () {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 404]));
        $adapter = new PackagistRegistryAdapter($client);

        $versions = $adapter->fetchVersions('nonexistent/package', PackageManager::Composer);

        expect($versions)->toBeEmpty();
    });

    it('supports only composer package manager', function () {
        $client = new MockHttpClient();
        $adapter = new PackagistRegistryAdapter($client);

        expect($adapter->supports(PackageManager::Composer))->toBeTrue();
        expect($adapter->supports(PackageManager::Npm))->toBeFalse();
    });
});
