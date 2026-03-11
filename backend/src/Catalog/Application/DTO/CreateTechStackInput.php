<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateTechStackInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public string $language,

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public string $framework,

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 50)]
        public string $version,

        #[Assert\NotBlank]
        public string $detectedAt,

        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $projectId,

        #[Assert\Length(max: 50)]
        public string $frameworkVersion = '',
    ) {
    }
}
