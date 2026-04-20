<?php

declare(strict_types=1);

namespace App\Hub\Shared\Domain\Port;

use App\Hub\Shared\Domain\DTO\OsvQuery;
use App\Hub\Shared\Domain\DTO\OsvVulnerability;

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
