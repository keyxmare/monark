<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\QueryHandler;

use App\Hub\Shared\Domain\Exception\NotFoundException;
use App\Monitoring\Catalog\Application\DTO\FrameworkOutput;
use App\Monitoring\Catalog\Application\Mapper\FrameworkMapper;
use App\Monitoring\Catalog\Application\Query\GetFrameworkQuery;
use App\Monitoring\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetFrameworkHandler
{
    public function __construct(private FrameworkRepositoryInterface $frameworkRepository)
    {
    }

    public function __invoke(GetFrameworkQuery $query): FrameworkOutput
    {
        $framework = $this->frameworkRepository->findById(Uuid::fromString($query->id));
        if ($framework === null) {
            throw NotFoundException::forEntity('Framework', $query->id);
        }

        return FrameworkMapper::toOutput($framework);
    }
}
