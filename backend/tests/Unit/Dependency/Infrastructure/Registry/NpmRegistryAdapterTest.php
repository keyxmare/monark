<?php

declare(strict_types=1);

use App\Dependency\Infrastructure\Registry\NpmRegistryAdapter;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

describe('NpmRegistryAdapter', function () {
    it('fetches versions with release dates from npm registry', function () {
        $responseData = \json_encode([
            'dist-tags' => ['latest' => '3.5.13'],
            'time' => [
                'created' => '2020-01-01T00:00:00Z',
                'modified' => '2026-03-01T00:00:00Z',
                '3.5.0' => '2025-09-01T10:00:00Z',
                '3.5.13' => '2026-02-15T10:00:00Z',
            ],
        ]);

        $client = new MockHttpClient(new MockResponse($responseData));
        $adapter = new NpmRegistryAdapter($client);

        $versions = $adapter->fetchVersions('vue', PackageManager::Npm);

        expect($versions)->toHaveCount(2);
        expect($versions[0]->version)->toBe('3.5.0');
        expect($versions[0]->releaseDate)->not->toBeNull();
        expect($versions[0]->isLatest)->toBeFalse();
        expect($versions[1]->version)->toBe('3.5.13');
        expect($versions[1]->isLatest)->toBeTrue();
    });

    it('filters versions since a given version', function () {
        $responseData = \json_encode([
            'dist-tags' => ['latest' => '3.5.13'],
            'time' => [
                'created' => '2020-01-01T00:00:00Z',
                '3.4.0' => '2025-06-01T10:00:00Z',
                '3.5.0' => '2025-09-01T10:00:00Z',
                '3.5.13' => '2026-02-15T10:00:00Z',
            ],
        ]);

        $client = new MockHttpClient(new MockResponse($responseData));
        $adapter = new NpmRegistryAdapter($client);

        $versions = $adapter->fetchVersions('vue', PackageManager::Npm, '3.5.0');

        expect($versions)->toHaveCount(1);
        expect($versions[0]->version)->toBe('3.5.13');
    });

    it('returns empty array on API error', function () {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 404]));
        $adapter = new NpmRegistryAdapter($client);

        $versions = $adapter->fetchVersions('nonexistent-package', PackageManager::Npm);

        expect($versions)->toBeEmpty();
    });

    it('supports only npm package manager', function () {
        $client = new MockHttpClient();
        $adapter = new NpmRegistryAdapter($client);

        expect($adapter->supports(PackageManager::Npm))->toBeTrue();
        expect($adapter->supports(PackageManager::Composer))->toBeFalse();
    });
});
