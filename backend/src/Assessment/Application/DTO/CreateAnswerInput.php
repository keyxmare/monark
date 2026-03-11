<?php

declare(strict_types=1);

namespace App\Assessment\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateAnswerInput
{
    public function __construct(
        #[Assert\NotBlank]
        public string $content,

        public bool $isCorrect,

        #[Assert\PositiveOrZero]
        public int $position,

        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $questionId,
    ) {
    }
}
