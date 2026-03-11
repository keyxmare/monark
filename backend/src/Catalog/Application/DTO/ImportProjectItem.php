<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ImportProjectItem
{
    public function __construct(
        #[Assert\NotBlank]
        public string $externalId,

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 255)]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 255)]
        public string $slug,

        public ?string $description = null,

        public string $repositoryUrl = '',

        public string $defaultBranch = 'main',

        #[Assert\NotBlank]
        public string $visibility = 'private',
    ) {
    }
}
