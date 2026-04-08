<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

use App\Shared\Domain\DTO\DependencyReadDTO;
use Symfony\Component\Uid\Uuid;

interface DependencyReaderPort
{
    /** @return list<DependencyReadDTO> */
    public function findByProjectId(Uuid $projectId): array;
}
