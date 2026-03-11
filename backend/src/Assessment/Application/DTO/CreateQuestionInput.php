<?php

declare(strict_types=1);

namespace App\Assessment\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateQuestionInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['single_choice', 'multiple_choice', 'text', 'code'])]
        public string $type,

        #[Assert\NotBlank]
        public string $content,

        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['easy', 'medium', 'hard'])]
        public string $level,

        #[Assert\PositiveOrZero]
        public int $score,

        #[Assert\PositiveOrZero]
        public int $position,

        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $quizId,
    ) {
    }
}
