<?php

declare(strict_types=1);

use App\VersionRegistry\Infrastructure\Resolver\EndOfLifeResolver;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

describe('EndOfLifeResolver', function () {
    it('supports endoflife source', function () {
        $resolver = new EndOfLifeResolver(new MockHttpClient());
        expect($resolver->supports('endoflife'))->toBeTrue();
        expect($resolver->supports('registry'))->toBeFalse();
    });

    it('fetches and maps cycles from endoflife.date API', function () {
        $cycles = [
            [
                'cycle' => '8.4',
                'releaseDate' => '2024-11-21',
                'eol' => '2028-12-31',
                'latest' => '8.4.2',
                'latestReleaseDate' => '2025-02-13',
                'lts' => false,
            ],
            [
                'cycle' => '8.3',
                'releaseDate' => '2023-11-23',
                'eol' => '2027-12-31',
                'latest' => '8.3.16',
                'latestReleaseDate' => '2025-01-16',
                'lts' => false,
            ],
        ];

        $client = new MockHttpClient([new MockResponse(\json_encode($cycles))]);
        $resolver = new EndOfLifeResolver($client);
        $versions = $resolver->fetchVersions('php');

        expect($versions)->toHaveCount(2);
        expect($versions[0]->version)->toBe('8.4.2');
        expect($versions[0]->isLatest)->toBeTrue();
        expect($versions[0]->eolDate)->toBe('2028-12-31');
        expect($versions[1]->version)->toBe('8.3.16');
        expect($versions[1]->isLatest)->toBeFalse();
    });

    it('marks LTS cycles', function () {
        $cycles = [['cycle' => '7.2', 'releaseDate' => '2024-11-01', 'eol' => '2028-11-01', 'latest' => '7.2.5', 'latestReleaseDate' => '2025-03-01', 'lts' => true]];
        $client = new MockHttpClient([new MockResponse(\json_encode($cycles))]);
        $resolver = new EndOfLifeResolver($client);
        $versions = $resolver->fetchVersions('symfony');
        expect($versions[0]->isLts)->toBeTrue();
    });

    it('returns empty array for unsupported product', function () {
        $resolver = new EndOfLifeResolver(new MockHttpClient());
        expect($resolver->fetchVersions('unknown-product-xyz'))->toBeEmpty();
    });

    it('returns empty array on API error', function () {
        $client = new MockHttpClient([new MockResponse('', ['http_code' => 404])]);
        $resolver = new EndOfLifeResolver($client);
        expect($resolver->fetchVersions('php'))->toBeEmpty();
    });
});
