<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class LoginInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,
        #[Assert\NotBlank]
        public string $password,
    ) {
    }
}
