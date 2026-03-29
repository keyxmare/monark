<?php

declare(strict_types=1);

use App\Dependency\Infrastructure\Registry\NpmRegistryAdapter;
use App\Shared\Domain\ValueObject\PackageManager;
use Psr\Log\LoggerInterface;
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
        expect($versions[0]->releaseDate->format('Y-m-d'))->toBe('2025-09-01');
        expect($versions[0]->isLatest)->toBeFalse();
        expect($versions[1]->version)->toBe('3.5.13');
        expect($versions[1]->isLatest)->toBeTrue();
        expect($versions[1]->releaseDate->format('Y-m-d'))->toBe('2026-02-15');
    });

    it('skips created and modified keys', function () {
        $responseData = \json_encode([
            'dist-tags' => ['latest' => '1.0.0'],
            'time' => [
                'created' => '2020-01-01T00:00:00Z',
                'modified' => '2026-03-01T00:00:00Z',
                '1.0.0' => '2025-06-01T10:00:00Z',
            ],
        ]);

        $client = new MockHttpClient(new MockResponse($responseData));
        $adapter = new NpmRegistryAdapter($client);

        $versions = $adapter->fetchVersions('some-pkg', PackageManager::Npm);

        expect($versions)->toHaveCount(1);
        expect($versions[0]->version)->toBe('1.0.0');
    });

    it('identifies latest version from dist-tags', function () {
        $responseData = \json_encode([
            'dist-tags' => ['latest' => '2.0.0'],
            'time' => [
                '1.0.0' => '2024-01-01T00:00:00Z',
                '2.0.0' => '2025-06-01T00:00:00Z',
                '3.0.0-beta.1' => '2025-12-01T00:00:00Z',
            ],
        ]);

        $client = new MockHttpClient(new MockResponse($responseData));
        $adapter = new NpmRegistryAdapter($client);

        $versions = $adapter->fetchVersions('some-pkg', PackageManager::Npm);

        expect($versions)->toHaveCount(3);
        expect($versions[0]->isLatest)->toBeFalse();
        expect($versions[0]->version)->toBe('1.0.0');
        expect($versions[1]->isLatest)->toBeTrue();
        expect($versions[1]->version)->toBe('2.0.0');
        expect($versions[2]->isLatest)->toBeFalse();
        expect($versions[2]->version)->toBe('3.0.0-beta.1');
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
        expect($versions[0]->isLatest)->toBeTrue();
    });

    it('excludes versions equal to sinceVersion', function () {
        $responseData = \json_encode([
            'dist-tags' => ['latest' => '2.0.0'],
            'time' => [
                '1.0.0' => '2024-01-01T00:00:00Z',
                '2.0.0' => '2025-06-01T00:00:00Z',
            ],
        ]);

        $client = new MockHttpClient(new MockResponse($responseData));
        $adapter = new NpmRegistryAdapter($client);

        $versions = $adapter->fetchVersions('some-pkg', PackageManager::Npm, '2.0.0');

        expect($versions)->toBeEmpty();
    });

    it('includes versions older than sinceVersion if they come after a newer one', function () {
        $responseData = \json_encode([
            'dist-tags' => ['latest' => '3.0.0'],
            'time' => [
                '1.0.0' => '2024-01-01T00:00:00Z',
                '3.0.0' => '2025-06-01T00:00:00Z',
                '2.0.0' => '2025-03-01T00:00:00Z',
            ],
        ]);

        $client = new MockHttpClient(new MockResponse($responseData));
        $adapter = new NpmRegistryAdapter($client);

        $versions = $adapter->fetchVersions('some-pkg', PackageManager::Npm, '1.5.0');

        expect(\count($versions))->toBeGreaterThanOrEqual(1);
        $versionStrings = \array_map(fn ($v) => $v->version, $versions);
        expect($versionStrings)->toContain('3.0.0');
    });

    it('returns empty array on 404', function () {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 404]));
        $adapter = new NpmRegistryAdapter($client);

        $versions = $adapter->fetchVersions('nonexistent-package', PackageManager::Npm);

        expect($versions)->toBeEmpty();
    });

    it('returns empty array on non-404 client error', function () {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 403]));
        $adapter = new NpmRegistryAdapter($client);

        $versions = $adapter->fetchVersions('forbidden-package', PackageManager::Npm);

        expect($versions)->toBeEmpty();
    });

    it('logs debug on 404', function () {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 404]));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('debug')
            ->with('npm package not found: {package}', ['package' => 'missing-pkg']);
        $logger->expects($this->never())->method('error');

        $adapter = new NpmRegistryAdapter($client, $logger);
        $adapter->fetchVersions('missing-pkg', PackageManager::Npm);
    });

    it('logs error on non-404 client error', function () {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 403]));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('debug');
        $logger->expects($this->once())->method('error');

        $adapter = new NpmRegistryAdapter($client, $logger);
        $adapter->fetchVersions('forbidden-pkg', PackageManager::Npm);
    });

    it('returns empty array on general exception', function () {
        $client = new MockHttpClient(function () {
            throw new RuntimeException('Network error');
        });
        $adapter = new NpmRegistryAdapter($client);

        $versions = $adapter->fetchVersions('some-pkg', PackageManager::Npm);

        expect($versions)->toBeEmpty();
    });

    it('supports only npm package manager', function () {
        $client = new MockHttpClient();
        $adapter = new NpmRegistryAdapter($client);

        expect($adapter->supports(PackageManager::Npm))->toBeTrue();
        expect($adapter->supports(PackageManager::Composer))->toBeFalse();
        expect($adapter->supports(PackageManager::Pip))->toBeFalse();
    });

    it('constructs correct URL for npm registry', function () {
        $requestedUrl = null;
        $client = new MockHttpClient(function ($method, $url) use (&$requestedUrl) {
            $requestedUrl = $url;

            return new MockResponse(\json_encode(['time' => [], 'dist-tags' => []]));
        });
        $adapter = new NpmRegistryAdapter($client);

        $adapter->fetchVersions('vue', PackageManager::Npm);

        expect($requestedUrl)->toBe('https://registry.npmjs.org/vue');
    });

    it('constructs correct URL for scoped npm packages', function () {
        $requestedUrl = null;
        $client = new MockHttpClient(function ($method, $url) use (&$requestedUrl) {
            $requestedUrl = $url;

            return new MockResponse(\json_encode(['time' => [], 'dist-tags' => []]));
        });
        $adapter = new NpmRegistryAdapter($client);

        $adapter->fetchVersions('@vue/core', PackageManager::Npm);

        expect($requestedUrl)->toBe('https://registry.npmjs.org/@vue/core');
    });

    it('uses GET method for API request', function () {
        $requestedMethod = null;
        $client = new MockHttpClient(function ($method, $url) use (&$requestedMethod) {
            $requestedMethod = $method;

            return new MockResponse(\json_encode(['time' => [], 'dist-tags' => []]));
        });
        $adapter = new NpmRegistryAdapter($client);

        $adapter->fetchVersions('vue', PackageManager::Npm);

        expect($requestedMethod)->toBe('GET');
    });

    it('handles missing dist-tags in response', function () {
        $responseData = \json_encode([
            'time' => [
                '1.0.0' => '2024-01-01T00:00:00Z',
            ],
        ]);

        $client = new MockHttpClient(new MockResponse($responseData));
        $adapter = new NpmRegistryAdapter($client);

        $versions = $adapter->fetchVersions('some-pkg', PackageManager::Npm);

        expect($versions)->toHaveCount(1);
        expect($versions[0]->isLatest)->toBeFalse();
    });

    it('handles missing time key in response', function () {
        $responseData = \json_encode([
            'dist-tags' => ['latest' => '1.0.0'],
        ]);

        $client = new MockHttpClient(new MockResponse($responseData));
        $adapter = new NpmRegistryAdapter($client);

        $versions = $adapter->fetchVersions('some-pkg', PackageManager::Npm);

        expect($versions)->toBeEmpty();
    });

    it('returns all versions when sinceVersion is null', function () {
        $responseData = \json_encode([
            'dist-tags' => ['latest' => '3.0.0'],
            'time' => [
                '1.0.0' => '2024-01-01T00:00:00Z',
                '2.0.0' => '2025-01-01T00:00:00Z',
                '3.0.0' => '2025-06-01T00:00:00Z',
            ],
        ]);

        $client = new MockHttpClient(new MockResponse($responseData));
        $adapter = new NpmRegistryAdapter($client);

        $versions = $adapter->fetchVersions('some-pkg', PackageManager::Npm, null);

        expect($versions)->toHaveCount(3);
    });

    it('handles none matching latest in dist-tags', function () {
        $responseData = \json_encode([
            'dist-tags' => ['latest' => '99.0.0'],
            'time' => [
                '1.0.0' => '2024-01-01T00:00:00Z',
                '2.0.0' => '2025-01-01T00:00:00Z',
            ],
        ]);

        $client = new MockHttpClient(new MockResponse($responseData));
        $adapter = new NpmRegistryAdapter($client);

        $versions = $adapter->fetchVersions('some-pkg', PackageManager::Npm);

        expect($versions)->toHaveCount(2);
        expect($versions[0]->isLatest)->toBeFalse();
        expect($versions[1]->isLatest)->toBeFalse();
    });
});
