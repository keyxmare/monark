<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

use App\Shared\Domain\DTO\MergeRequestReadDTO;
use Symfony\Component\Uid\Uuid;

interface MergeRequestReaderPort
{
    /** @return list<MergeRequestReadDTO> */
    public function findActiveByProjectId(Uuid $projectId): array;
}
