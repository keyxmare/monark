<?php

declare(strict_types=1);

namespace Tests\Factory\Catalog;

use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\ProviderType;

final class ProviderFactory
{
    public static function create(
        string $name = 'GitLab Test',
        ProviderType $type = ProviderType::GitLab,
        string $url = 'https://gitlab.example.com',
        ?string $apiToken = 'glpat-test-token-123',
        ?string $username = null,
    ): Provider {
        return Provider::create(
            name: $name,
            type: $type,
            url: $url,
            apiToken: $apiToken,
            username: $username,
        );
    }
}
