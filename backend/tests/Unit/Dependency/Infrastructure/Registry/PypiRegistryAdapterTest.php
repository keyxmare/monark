<?php

declare(strict_types=1);

use App\Dependency\Infrastructure\Registry\PypiRegistryAdapter;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

function makePypiHttpClient(int $statusCode, array $data): HttpClientInterface
{
    return new class ($statusCode, $data) implements HttpClientInterface {
        public function __construct(
            private readonly int $statusCode,
            private readonly array $data,
        ) {
        }

        public function request(string $method, string $url, array $options = []): ResponseInterface
        {
            return new class ($this->statusCode, $this->data) implements ResponseInterface {
                public function __construct(
                    private readonly int $status,
                    private readonly array $data,
                ) {
                }

                public function getStatusCode(): int
                {
                    return $this->status;
                }

                public function getHeaders(bool $throw = true): array
                {
                    return [];
                }

                public function getContent(bool $throw = true): string
                {
                    return (string) \json_encode($this->data);
                }

                public function toArray(bool $throw = true): array
                {
                    return $this->data;
                }

                public function cancel(): void
                {
                }

                public function getInfo(?string $type = null): mixed
                {
                    return null;
                }
            };
        }

        public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): \Symfony\Contracts\HttpClient\ResponseStreamInterface
        {
            throw new \RuntimeException('Not implemented');
        }

        public function withOptions(array $options): static
        {
            return $this;
        }
    };
}

describe('PypiRegistryAdapter', function () {
    it('supports only pip package manager', function () {
        $adapter = new PypiRegistryAdapter(test()->createMock(HttpClientInterface::class));

        expect($adapter->supports(PackageManager::Pip))->toBeTrue()
            ->and($adapter->supports(PackageManager::Npm))->toBeFalse()
            ->and($adapter->supports(PackageManager::Composer))->toBeFalse();
    });

    it('returns versions from PyPI JSON API', function () {
        $data = [
            'info' => ['version' => '4.2.0'],
            'releases' => [
                '4.0.0' => [['upload_time' => '2023-01-15T10:00:00']],
                '4.1.0' => [['upload_time' => '2023-06-01T10:00:00']],
                '4.2.0' => [['upload_time' => '2024-01-10T10:00:00']],
            ],
        ];
        $httpClient = \makePypiHttpClient(200, $data);
        $adapter = new PypiRegistryAdapter($httpClient);

        $versions = $adapter->fetchVersions('requests', PackageManager::Pip);

        expect($versions)->toHaveCount(3);
    });

    it('marks the latest version from info.version', function () {
        $data = [
            'info' => ['version' => '4.2.0'],
            'releases' => [
                '4.1.0' => [['upload_time' => '2023-06-01T10:00:00']],
                '4.2.0' => [['upload_time' => '2024-01-10T10:00:00']],
            ],
        ];
        $httpClient = \makePypiHttpClient(200, $data);
        $adapter = new PypiRegistryAdapter($httpClient);

        $versions = $adapter->fetchVersions('requests', PackageManager::Pip);

        $latest = \array_filter($versions, static fn ($v) => $v->isLatest);
        expect(\array_values($latest)[0]->version)->toBe('4.2.0');
    });

    it('filters out versions older than sinceVersion', function () {
        $data = [
            'info' => ['version' => '4.2.0'],
            'releases' => [
                '4.0.0' => [['upload_time' => '2023-01-15T10:00:00']],
                '4.1.0' => [['upload_time' => '2023-06-01T10:00:00']],
                '4.2.0' => [['upload_time' => '2024-01-10T10:00:00']],
            ],
        ];
        $httpClient = \makePypiHttpClient(200, $data);
        $adapter = new PypiRegistryAdapter($httpClient);

        $versions = $adapter->fetchVersions('requests', PackageManager::Pip, sinceVersion: '4.1.0');

        expect($versions)->toHaveCount(1)
            ->and($versions[0]->version)->toBe('4.2.0');
    });

    it('returns empty array when package not found', function () {
        $httpClient = new class () implements HttpClientInterface {
            public function request(string $method, string $url, array $options = []): ResponseInterface
            {
                throw new class () extends \RuntimeException implements \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface {
                    public function getResponse(): ResponseInterface
                    {
                        return new class () implements ResponseInterface {
                            public function getStatusCode(): int
                            {
                                return 404;
                            }
                            public function getHeaders(bool $throw = true): array
                            {
                                return [];
                            }
                            public function getContent(bool $throw = true): string
                            {
                                return '';
                            }
                            public function toArray(bool $throw = true): array
                            {
                                return [];
                            }
                            public function cancel(): void
                            {
                            }
                            public function getInfo(?string $type = null): mixed
                            {
                                return null;
                            }
                        };
                    }
                };
            }

            public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): \Symfony\Contracts\HttpClient\ResponseStreamInterface
            {
                throw new \RuntimeException('Not implemented');
            }

            public function withOptions(array $options): static
            {
                return $this;
            }
        };

        $adapter = new PypiRegistryAdapter($httpClient);

        expect($adapter->fetchVersions('nonexistent-pkg', PackageManager::Pip))->toBeEmpty();
    });

    it('skips release entries with no upload files', function () {
        $data = [
            'info' => ['version' => '1.0.0'],
            'releases' => [
                '0.9.0' => [],
                '1.0.0' => [['upload_time' => '2024-01-01T00:00:00']],
            ],
        ];
        $httpClient = \makePypiHttpClient(200, $data);
        $adapter = new PypiRegistryAdapter($httpClient);

        $versions = $adapter->fetchVersions('mypkg', PackageManager::Pip);

        expect($versions)->toHaveCount(1)
            ->and($versions[0]->version)->toBe('1.0.0');
    });
});
