<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateAccessTokenInput
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['gitlab', 'github'])]
        public string $provider,
        #[Assert\NotBlank]
        public string $token,
        #[Assert\NotNull]
        public array $scopes = [],
        public ?string $expiresAt = null,
    ) {
    }
}
