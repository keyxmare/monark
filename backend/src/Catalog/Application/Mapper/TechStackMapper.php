<?php

declare(strict_types=1);

namespace App\Catalog\Application\Mapper;

use App\Catalog\Application\DTO\TechStackOutput;
use App\Catalog\Domain\Model\TechStack;
use DateTimeInterface;

final class TechStackMapper
{
    public static function toOutput(TechStack $techStack): TechStackOutput
    {
        return new TechStackOutput(
            id: $techStack->getId()->toRfc4122(),
            language: $techStack->getLanguage(),
            framework: $techStack->getFramework(),
            version: $techStack->getVersion(),
            frameworkVersion: $techStack->getFrameworkVersion(),
            detectedAt: $techStack->getDetectedAt()->format(DateTimeInterface::ATOM),
            projectId: $techStack->getProject()->getId()->toRfc4122(),
            createdAt: $techStack->getCreatedAt()->format(DateTimeInterface::ATOM),
            latestLts: $techStack->getLatestLts(),
            ltsGap: $techStack->getLtsGap(),
            maintenanceStatus: $techStack->getMaintenanceStatus(),
            eolDate: $techStack->getEolDate()?->format('Y-m-d'),
            versionSyncedAt: $techStack->getVersionSyncedAt()?->format(DateTimeInterface::ATOM),
        );
    }
}
