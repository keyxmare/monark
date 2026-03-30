<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateLanguageInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public string $name,
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 50)]
        public string $version,
        #[Assert\NotBlank]
        public string $detectedAt,
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $projectId,
    ) {
    }
}
