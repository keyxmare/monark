<?php

declare(strict_types=1);

namespace App\Activity\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateBuildMetricInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 40)]
        public string $commitSha,
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $ref,
        #[Assert\Range(min: 0, max: 100)]
        public ?float $backendCoverage = null,
        #[Assert\Range(min: 0, max: 100)]
        public ?float $frontendCoverage = null,
        #[Assert\Range(min: 0, max: 100)]
        public ?float $mutationScore = null,
    ) {
    }
}
