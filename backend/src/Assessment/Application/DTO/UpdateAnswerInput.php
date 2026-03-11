<?php

declare(strict_types=1);

namespace App\Assessment\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateAnswerInput
{
    public function __construct(
        public ?string $content = null,

        public ?bool $isCorrect = null,

        #[Assert\PositiveOrZero]
        public ?int $position = null,
    ) {
    }
}
