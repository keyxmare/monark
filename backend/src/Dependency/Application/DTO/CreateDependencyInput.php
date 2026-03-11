<?php

declare(strict_types=1);

namespace App\Dependency\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateDependencyInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 255)]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public string $currentVersion,

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public string $latestVersion,

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public string $ltsVersion,

        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['composer', 'npm', 'pip'])]
        public string $packageManager,

        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['runtime', 'dev'])]
        public string $type,

        public bool $isOutdated,

        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $projectId,
    ) {
    }
}
