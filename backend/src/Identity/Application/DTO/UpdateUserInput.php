<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateUserInput
{
    public function __construct(
        #[Assert\Length(min: 1, max: 100)]
        public ?string $firstName = null,
        #[Assert\Length(min: 1, max: 100)]
        public ?string $lastName = null,
        #[Assert\Length(max: 255)]
        public ?string $avatar = null,
        #[Assert\Email]
        public ?string $email = null,
    ) {
    }
}
