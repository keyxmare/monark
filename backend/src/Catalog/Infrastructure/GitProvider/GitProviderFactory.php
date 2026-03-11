<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\GitProvider;

use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\ProviderType;
use App\Catalog\Domain\Port\GitProviderInterface;

class GitProviderFactory
{
    public function __construct(
        private readonly GitLabClient $gitLabClient,
    ) {
    }

    public function create(Provider $provider): GitProviderInterface
    {
        return match ($provider->getType()) {
            ProviderType::GitLab => $this->gitLabClient,
            default => throw new \InvalidArgumentException(\sprintf('Unsupported provider type: %s', $provider->getType()->value)),
        };
    }
}
