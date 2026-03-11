<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

use App\Catalog\Domain\Model\PipelineStatus;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreatePipelineInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 255)]
        public string $externalId,

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 255)]
        public string $ref,

        #[Assert\NotNull]
        public PipelineStatus $status,

        #[Assert\PositiveOrZero]
        public int $duration,

        #[Assert\NotBlank]
        public string $startedAt,

        public ?string $finishedAt = null,

        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $projectId = '',
    ) {
    }
}
