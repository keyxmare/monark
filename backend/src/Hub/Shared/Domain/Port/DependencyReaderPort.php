<?php

declare(strict_types=1);

namespace App\Hub\Shared\Domain\Port;

use App\Hub\Shared\Domain\DTO\DependencyReadDTO;
use Symfony\Component\Uid\Uuid;

interface DependencyReaderPort
{
    /** @return list<DependencyReadDTO> */
    public function findByProjectId(Uuid $projectId): array;
}
