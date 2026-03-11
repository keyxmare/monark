<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ImportProjectsInput
{
    /** @var list<ImportProjectItem> */
    #[Assert\NotBlank]
    #[Assert\Valid]
    public array $projects;

    public function __construct(array $projects)
    {
        $this->projects = \array_map(
            static fn(array|ImportProjectItem $item): ImportProjectItem => $item instanceof ImportProjectItem
                ? $item
                : new ImportProjectItem(...$item),
            $projects,
        );
    }
}
