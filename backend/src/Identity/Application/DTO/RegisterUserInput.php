<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterUserInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 128)]
        public string $password,
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public string $firstName,
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public string $lastName,
    ) {
    }
}
