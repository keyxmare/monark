<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

use App\Catalog\Domain\Model\ProjectVisibility;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateProjectInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 255)]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 255)]
        #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
        public string $slug,

        public ?string $description = null,

        #[Assert\NotBlank]
        #[Assert\Url]
        public string $repositoryUrl = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public string $defaultBranch = 'main',

        #[Assert\NotNull]
        public ProjectVisibility $visibility = ProjectVisibility::Private,

        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $ownerId = '',
    ) {
    }
}
