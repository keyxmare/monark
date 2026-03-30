<?php

declare(strict_types=1);

namespace App\Catalog\Application\Mapper;

use App\Catalog\Application\DTO\LanguageOutput;
use App\Catalog\Domain\Model\Language;
use DateTimeInterface;

final class LanguageMapper
{
    public static function toOutput(Language $language): LanguageOutput
    {
        return new LanguageOutput(
            id: $language->getId()->toRfc4122(),
            name: $language->getName(),
            version: $language->getVersion(),
            detectedAt: $language->getDetectedAt()->format(DateTimeInterface::ATOM),
            projectId: $language->getProject()->getId()->toRfc4122(),
            createdAt: $language->getCreatedAt()->format(DateTimeInterface::ATOM),
            updatedAt: $language->getUpdatedAt()->format(DateTimeInterface::ATOM),
            eolDate: $language->getEolDate()?->format('Y-m-d'),
            maintenanceStatus: $language->getMaintenanceStatus(),
        );
    }
}
