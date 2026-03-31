<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

use App\Shared\Domain\DTO\OsvQuery;
use App\Shared\Domain\DTO\OsvVulnerability;

interface OsvClientInterface
{
    /** @return list<OsvVulnerability> */
    public function queryPackage(string $ecosystem, string $name, string $version): array;

    /**
     * @param list<OsvQuery> $queries
     * @return list<list<OsvVulnerability>>
     */
    public function queryBatch(array $queries): array;
}
