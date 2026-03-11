<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateProviderInput
{
    public function __construct(
        #[Assert\Length(min: 1, max: 255)]
        public ?string $name = null,

        #[Assert\Url]
        #[Assert\Length(max: 500)]
        public ?string $url = null,

        public ?string $apiToken = null,

        #[Assert\Length(max: 255)]
        public ?string $username = null,
    ) {
    }
}
