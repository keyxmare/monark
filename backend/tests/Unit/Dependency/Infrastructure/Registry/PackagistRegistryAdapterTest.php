<?php

declare(strict_types=1);

use App\Dependency\Infrastructure\Registry\PackagistRegistryAdapter;
use App\Shared\Domain\ValueObject\PackageManager;
use Psr\Log\LoggerInterface;
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
        expect($versions[0]->releaseDate->format('Y-m-d'))->toBe('2026-03-06');
        expect($versions[1]->version)->toBe('8.0.6');
        expect($versions[1]->isLatest)->toBeFalse();
        expect($versions[1]->releaseDate->format('Y-m-d'))->toBe('2026-02-25');
        expect($versions[2]->version)->toBe('7.4.7');
        expect($versions[2]->isLatest)->toBeFalse();
        expect($versions[2]->releaseDate->format('Y-m-d'))->toBe('2026-02-25');
    });

    it('strips v prefix from version strings', function () {
        $responseData = \json_encode([
            'packages' => [
                'some/lib' => [
                    ['version' => 'v2.0.0'],
                    ['version' => '1.0.0'],
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

    it('marks only the first stable version as latest', function () {
        $responseData = \json_encode([
            'packages' => [
                'some/lib' => [
                    ['version' => 'v3.0.0'],
                    ['version' => 'v2.0.0'],
                    ['version' => 'v1.0.0'],
                ],
            ],
        ]);

        $client = new MockHttpClient(new MockResponse($responseData));
        $adapter = new PackagistRegistryAdapter($client);

        $versions = $adapter->fetchVersions('some/lib', PackageManager::Composer);

        expect($versions)->toHaveCount(3);
        expect($versions[0]->isLatest)->toBeTrue();
        expect($versions[1]->isLatest)->toBeFalse();
        expect($versions[2]->isLatest)->toBeFalse();
    });

    it('filters dev and pre-release versions', function () {
        $responseData = \json_encode([
            'packages' => [
                'some/lib' => [
                    ['version' => 'v2.0.0', 'time' => '2026-01-01T00:00:00+00:00'],
                    ['version' => 'v2.0.0-beta1', 'time' => '2025-12-01T00:00:00+00:00'],
                    ['version' => 'dev-main', 'time' => '2026-03-01T00:00:00+00:00'],
                    ['version' => 'v2.0.0-alpha1', 'time' => '2025-11-01T00:00:00+00:00'],
                    ['version' => 'v2.0.0-RC1', 'time' => '2025-12-15T00:00:00+00:00'],
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

    it('filters versions since a given version using version_compare', function () {
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
        expect($versions[0]->isLatest)->toBeTrue();
    });

    it('excludes the exact sinceVersion from results', function () {
        $responseData = \json_encode([
            'packages' => [
                'some/lib' => [
                    ['version' => 'v2.0.0'],
                    ['version' => 'v1.0.0'],
                ],
            ],
        ]);

        $client = new MockHttpClient(new MockResponse($responseData));
        $adapter = new PackagistRegistryAdapter($client);

        $versions = $adapter->fetchVersions('some/lib', PackageManager::Composer, '2.0.0');

        expect($versions)->toBeEmpty();
    });

    it('returns empty array on 404', function () {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 404]));
        $adapter = new PackagistRegistryAdapter($client);

        $versions = $adapter->fetchVersions('nonexistent/package', PackageManager::Composer);

        expect($versions)->toBeEmpty();
    });

    it('returns empty array on non-404 client error', function () {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 403]));
        $adapter = new PackagistRegistryAdapter($client);

        $versions = $adapter->fetchVersions('forbidden/package', PackageManager::Composer);

        expect($versions)->toBeEmpty();
    });

    it('logs debug on 404', function () {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 404]));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('debug')
            ->with('Packagist package not found: {package}', ['package' => 'missing/pkg']);
        $logger->expects($this->never())->method('error');

        $adapter = new PackagistRegistryAdapter($client, $logger);
        $adapter->fetchVersions('missing/pkg', PackageManager::Composer);
    });

    it('logs error on non-404 client error', function () {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 403]));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('debug');
        $logger->expects($this->once())->method('error');

        $adapter = new PackagistRegistryAdapter($client, $logger);
        $adapter->fetchVersions('forbidden/pkg', PackageManager::Composer);
    });

    it('returns empty array on general exception', function () {
        $client = new MockHttpClient(function () {
            throw new RuntimeException('Network error');
        });
        $adapter = new PackagistRegistryAdapter($client);

        $versions = $adapter->fetchVersions('some/lib', PackageManager::Composer);

        expect($versions)->toBeEmpty();
    });

    it('supports only composer package manager', function () {
        $client = new MockHttpClient();
        $adapter = new PackagistRegistryAdapter($client);

        expect($adapter->supports(PackageManager::Composer))->toBeTrue();
        expect($adapter->supports(PackageManager::Npm))->toBeFalse();
        expect($adapter->supports(PackageManager::Pip))->toBeFalse();
    });

    it('constructs correct URL for packagist API', function () {
        $requestedUrl = null;
        $client = new MockHttpClient(function ($method, $url) use (&$requestedUrl) {
            $requestedUrl = $url;

            return new MockResponse(\json_encode(['packages' => []]));
        });
        $adapter = new PackagistRegistryAdapter($client);

        $adapter->fetchVersions('vendor/package', PackageManager::Composer);

        expect($requestedUrl)->toBe('https://repo.packagist.org/p2/vendor/package.json');
    });

    it('uses GET method for API request', function () {
        $requestedMethod = null;
        $client = new MockHttpClient(function ($method, $url) use (&$requestedMethod) {
            $requestedMethod = $method;

            return new MockResponse(\json_encode(['packages' => []]));
        });
        $adapter = new PackagistRegistryAdapter($client);

        $adapter->fetchVersions('vendor/package', PackageManager::Composer);

        expect($requestedMethod)->toBe('GET');
    });

    it('handles missing time field gracefully with null releaseDate', function () {
        $responseData = \json_encode([
            'packages' => [
                'some/lib' => [
                    ['version' => 'v1.0.0'],
                ],
            ],
        ]);

        $client = new MockHttpClient(new MockResponse($responseData));
        $adapter = new PackagistRegistryAdapter($client);

        $versions = $adapter->fetchVersions('some/lib', PackageManager::Composer);

        expect($versions)->toHaveCount(1);
        expect($versions[0]->releaseDate)->toBeNull();
    });

    it('handles empty version string', function () {
        $responseData = \json_encode([
            'packages' => [
                'some/lib' => [
                    ['version' => '', 'time' => '2026-01-01T00:00:00+00:00'],
                    ['version' => 'v1.0.0', 'time' => '2025-06-01T00:00:00+00:00'],
                ],
            ],
        ]);

        $client = new MockHttpClient(new MockResponse($responseData));
        $adapter = new PackagistRegistryAdapter($client);

        $versions = $adapter->fetchVersions('some/lib', PackageManager::Composer);

        expect($versions)->toHaveCount(1);
        expect($versions[0]->version)->toBe('1.0.0');
    });

    it('handles missing packages key in response', function () {
        $responseData = \json_encode(['something_else' => true]);

        $client = new MockHttpClient(new MockResponse($responseData));
        $adapter = new PackagistRegistryAdapter($client);

        $versions = $adapter->fetchVersions('some/lib', PackageManager::Composer);

        expect($versions)->toBeEmpty();
    });

    it('handles missing package name key in packages', function () {
        $responseData = \json_encode([
            'packages' => [
                'other/lib' => [
                    ['version' => 'v1.0.0'],
                ],
            ],
        ]);

        $client = new MockHttpClient(new MockResponse($responseData));
        $adapter = new PackagistRegistryAdapter($client);

        $versions = $adapter->fetchVersions('some/lib', PackageManager::Composer);

        expect($versions)->toBeEmpty();
    });

    it('returns all versions when sinceVersion is null', function () {
        $responseData = \json_encode([
            'packages' => [
                'some/lib' => [
                    ['version' => 'v3.0.0'],
                    ['version' => 'v2.0.0'],
                    ['version' => 'v1.0.0'],
                ],
            ],
        ]);

        $client = new MockHttpClient(new MockResponse($responseData));
        $adapter = new PackagistRegistryAdapter($client);

        $versions = $adapter->fetchVersions('some/lib', PackageManager::Composer, null);

        expect($versions)->toHaveCount(3);
    });
});
