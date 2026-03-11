<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateTeamInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 150)]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 150)]
        #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
        public string $slug,

        public ?string $description = null,
    ) {
    }
}
