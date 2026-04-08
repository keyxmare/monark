<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

use App\Catalog\Domain\Model\ProjectVisibility;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateProjectInput
{
    public function __construct(
        #[Assert\Length(min: 1, max: 255)]
        public ?string $name = null,
        #[Assert\Length(min: 1, max: 255)]
        #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
        public ?string $slug = null,
        public ?string $description = null,
        #[Assert\Url]
        public ?string $repositoryUrl = null,
        #[Assert\Length(min: 1, max: 100)]
        public ?string $defaultBranch = null,
        public ?ProjectVisibility $visibility = null,
    ) {
    }
}
