<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

use Symfony\Component\Uid\Uuid;

interface DependencyWriterPort
{
    public function deleteByProjectId(Uuid $projectId): void;

    public function createFromScan(
        string $name,
        string $currentVersion,
        string $packageManager,
        string $type,
        Uuid $projectId,
        ?string $repositoryUrl,
    ): void;
}
