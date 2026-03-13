<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\GitProvider;

use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\ProviderType;
use App\Catalog\Domain\Port\GitProviderFactoryInterface;
use App\Catalog\Domain\Port\GitProviderInterface;
use InvalidArgumentException;

class GitProviderFactory implements GitProviderFactoryInterface
{
    public function __construct(
        private readonly GitLabClient $gitLabClient,
        private readonly GitHubClient $gitHubClient,
    ) {
    }

    public function create(Provider $provider): GitProviderInterface
    {
        return match ($provider->getType()) {
            ProviderType::GitLab => $this->gitLabClient,
            ProviderType::GitHub => $this->gitHubClient,
            default => throw new InvalidArgumentException(\sprintf('Unsupported provider type: %s', $provider->getType()->value)),
        };
    }
}
