<?php

declare(strict_types=1);

namespace App\Catalog\Application\Mapper;

use App\Catalog\Application\DTO\FrameworkOutput;
use App\Catalog\Domain\Model\Framework;
use DateTimeInterface;

final class FrameworkMapper
{
    public static function toOutput(Framework $framework): FrameworkOutput
    {
        return new FrameworkOutput(
            id: $framework->getId()->toRfc4122(),
            name: $framework->getName(),
            version: $framework->getVersion(),
            detectedAt: $framework->getDetectedAt()->format(DateTimeInterface::ATOM),
            languageId: $framework->getLanguage()->getId()->toRfc4122(),
            languageName: $framework->getLanguage()->getName(),
            projectId: $framework->getProject()->getId()->toRfc4122(),
            createdAt: $framework->getCreatedAt()->format(DateTimeInterface::ATOM),
            updatedAt: $framework->getUpdatedAt()->format(DateTimeInterface::ATOM),
            latestLts: $framework->getLatestLts(),
            ltsGap: $framework->getLtsGap(),
            maintenanceStatus: $framework->getMaintenanceStatus(),
            eolDate: $framework->getEolDate()?->format('Y-m-d'),
            versionSyncedAt: $framework->getVersionSyncedAt()?->format(DateTimeInterface::ATOM),
        );
    }
}
