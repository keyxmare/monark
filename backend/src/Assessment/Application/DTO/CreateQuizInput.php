<?php

declare(strict_types=1);

namespace App\Assessment\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateQuizInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 255)]
        public string $title,
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 255)]
        #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/')]
        public string $slug,
        #[Assert\NotBlank]
        public string $description,
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['quiz', 'survey'])]
        public string $type,
        #[Assert\Choice(choices: ['draft', 'published', 'archived'])]
        public string $status = 'draft',
        public ?string $startsAt = null,
        public ?string $endsAt = null,
        #[Assert\PositiveOrZero]
        public ?int $timeLimit = null,
        public string $authorId = '',
    ) {
    }
}
