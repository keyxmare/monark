<?php

declare(strict_types=1);

namespace App\Catalog\Application\QueryHandler;

use App\Catalog\Application\DTO\LanguageOutput;
use App\Catalog\Application\Mapper\LanguageMapper;
use App\Catalog\Application\Query\ListLanguagesQuery;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListLanguagesHandler
{
    public function __construct(
        private LanguageRepositoryInterface $languageRepository,
    ) {
    }

    /** @return list<LanguageOutput> */
    public function __invoke(ListLanguagesQuery $query): array
    {
        $languages = $query->projectId !== null
            ? $this->languageRepository->findByProjectId(Uuid::fromString($query->projectId))
            : $this->languageRepository->findAll();

        return \array_map(LanguageMapper::toOutput(...), $languages);
    }
}
