<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

use App\Catalog\Domain\Model\ProviderType;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateProviderInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 255)]
        public string $name,

        #[Assert\NotNull]
        public ProviderType $type,

        #[Assert\NotBlank]
        #[Assert\Url]
        #[Assert\Length(max: 500)]
        public string $url,

        public ?string $apiToken = null,

        #[Assert\Length(max: 255)]
        public ?string $username = null,
    ) {
    }
}
