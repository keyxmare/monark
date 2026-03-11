<?php

declare(strict_types=1);

namespace App\Activity\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateActivityEventInput
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        #[Assert\NotBlank]
        public string $type,

        #[Assert\NotBlank]
        public string $entityType,

        #[Assert\NotBlank]
        public string $entityId,

        #[Assert\NotNull]
        public array $payload = [],

        #[Assert\NotBlank]
        public string $userId = '',
    ) {
    }
}
