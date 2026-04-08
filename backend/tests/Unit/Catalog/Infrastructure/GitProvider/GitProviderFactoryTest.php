<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\ProviderType;
use App\Catalog\Domain\Port\GitProviderInterface;
use App\Catalog\Infrastructure\GitProvider\GitHubClient;
use App\Catalog\Infrastructure\GitProvider\GitLabClient;
use App\Catalog\Infrastructure\GitProvider\GitProviderFactory;
use Symfony\Contracts\HttpClient\HttpClientInterface;

it('returns gitlab client for gitlab provider', function () {
    $httpClient = $this->createMock(HttpClientInterface::class);
    $gitLabClient = new GitLabClient($httpClient);
    $gitHubClient = new GitHubClient($httpClient);
    $factory = new GitProviderFactory($gitLabClient, $gitHubClient);

    $provider = Provider::create('GitLab', ProviderType::GitLab, 'https://gitlab.com', 'token');

    $result = $factory->create($provider);

    expect($result)->toBe($gitLabClient);
    expect($result)->toBeInstanceOf(GitProviderInterface::class);
});

it('returns github client for github provider', function () {
    $httpClient = $this->createMock(HttpClientInterface::class);
    $gitLabClient = new GitLabClient($httpClient);
    $gitHubClient = new GitHubClient($httpClient);
    $factory = new GitProviderFactory($gitLabClient, $gitHubClient);

    $provider = Provider::create('GitHub', ProviderType::GitHub, 'https://github.com', null, 'user');

    $result = $factory->create($provider);

    expect($result)->toBe($gitHubClient);
});
