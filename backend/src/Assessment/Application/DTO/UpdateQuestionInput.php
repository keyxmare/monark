<?php

declare(strict_types=1);

namespace App\Assessment\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateQuestionInput
{
    public function __construct(
        #[Assert\Choice(choices: ['single_choice', 'multiple_choice', 'text', 'code'])]
        public ?string $type = null,

        public ?string $content = null,

        #[Assert\Choice(choices: ['easy', 'medium', 'hard'])]
        public ?string $level = null,

        #[Assert\PositiveOrZero]
        public ?int $score = null,

        #[Assert\PositiveOrZero]
        public ?int $position = null,
    ) {
    }
}
