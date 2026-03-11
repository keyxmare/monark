<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

use App\Catalog\Domain\Model\TechStack;

final readonly class TechStackOutput
{
    public function __construct(
        public string $id,
        public string $language,
        public string $framework,
        public string $version,
        public string $frameworkVersion,
        public string $detectedAt,
        public string $projectId,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(TechStack $techStack): self
    {
        return new self(
            id: $techStack->getId()->toRfc4122(),
            language: $techStack->getLanguage(),
            framework: $techStack->getFramework(),
            version: $techStack->getVersion(),
            frameworkVersion: $techStack->getFrameworkVersion(),
            detectedAt: $techStack->getDetectedAt()->format(\DateTimeInterface::ATOM),
            projectId: $techStack->getProject()->getId()->toRfc4122(),
            createdAt: $techStack->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
