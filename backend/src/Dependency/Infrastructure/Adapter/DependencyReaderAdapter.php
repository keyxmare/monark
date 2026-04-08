<?php

declare(strict_types=1);

namespace App\Dependency\Infrastructure\Adapter;

use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\DTO\DependencyReadDTO;
use App\Shared\Domain\DTO\VulnerabilityReadDTO;
use App\Shared\Domain\Port\DependencyReaderPort;
use Symfony\Component\Uid\Uuid;

final readonly class DependencyReaderAdapter implements DependencyReaderPort
{
    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
    ) {
    }

    /** @return list<DependencyReadDTO> */
    public function findByProjectId(Uuid $projectId): array
    {
        $dependencies = $this->dependencyRepository->findByProjectId($projectId, 1, 1000);

        return \array_map(
            fn ($dep) => new DependencyReadDTO(
                name: $dep->getName(),
                currentVersion: $dep->getCurrentVersion(),
                latestVersion: $dep->getLatestVersion(),
                packageManager: $dep->getPackageManager()->value,
                isOutdated: $dep->isOutdated(),
                vulnerabilities: \array_values(\array_map(
                    fn ($vuln) => new VulnerabilityReadDTO(
                        cveId: $vuln->getCveId(),
                        severity: $vuln->getSeverity()->value,
                        title: $vuln->getTitle(),
                        description: $vuln->getDescription(),
                        patchedVersion: $vuln->getPatchedVersion(),
                        status: $vuln->getStatus()->value,
                    ),
                    $dep->getVulnerabilities()->toArray(),
                )),
            ),
            $dependencies,
        );
    }
}
