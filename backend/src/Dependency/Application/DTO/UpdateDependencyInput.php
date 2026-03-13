<?php

declare(strict_types=1);

namespace App\Dependency\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateDependencyInput
{
    public function __construct(
        #[Assert\Length(min: 1, max: 255)]
        public ?string $name = null,
        #[Assert\Length(min: 1, max: 100)]
        public ?string $currentVersion = null,
        #[Assert\Length(min: 1, max: 100)]
        public ?string $latestVersion = null,
        #[Assert\Length(min: 1, max: 100)]
        public ?string $ltsVersion = null,
        #[Assert\Choice(choices: ['composer', 'npm', 'pip'])]
        public ?string $packageManager = null,
        #[Assert\Choice(choices: ['runtime', 'dev'])]
        public ?string $type = null,
        public ?bool $isOutdated = null,
        #[Assert\Url]
        #[Assert\Length(max: 2048)]
        public ?string $repositoryUrl = null,
    ) {
    }
}
