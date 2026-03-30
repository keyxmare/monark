<?php

declare(strict_types=1);

namespace App\Catalog\Application\QueryHandler;

use App\Catalog\Application\DTO\LanguageOutput;
use App\Catalog\Application\Mapper\LanguageMapper;
use App\Catalog\Application\Query\GetLanguageQuery;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetLanguageHandler
{
    public function __construct(
        private LanguageRepositoryInterface $languageRepository,
    ) {
    }

    public function __invoke(GetLanguageQuery $query): LanguageOutput
    {
        $language = $this->languageRepository->findById(Uuid::fromString($query->id));
        if ($language === null) {
            throw NotFoundException::forEntity('Language', $query->id);
        }

        return LanguageMapper::toOutput($language);
    }
}
