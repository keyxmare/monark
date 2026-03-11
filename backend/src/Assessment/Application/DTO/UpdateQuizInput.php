<?php

declare(strict_types=1);

namespace App\Assessment\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateQuizInput
{
    public function __construct(
        #[Assert\Length(min: 1, max: 255)]
        public ?string $title = null,

        #[Assert\Length(min: 1, max: 255)]
        #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
        public ?string $slug = null,

        public ?string $description = null,

        #[Assert\Choice(choices: ['quiz', 'survey'])]
        public ?string $type = null,

        #[Assert\Choice(choices: ['draft', 'published', 'archived'])]
        public ?string $status = null,

        public ?string $startsAt = null,

        public ?string $endsAt = null,

        #[Assert\PositiveOrZero]
        public ?int $timeLimit = null,
    ) {
    }
}
