<?php

declare(strict_types=1);

namespace App\Identity\Application\QueryHandler;

use App\Identity\Application\DTO\TeamOutput;
use App\Identity\Application\Query\GetTeamQuery;
use App\Identity\Domain\Repository\TeamRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetTeamHandler
{
    public function __construct(
        private TeamRepositoryInterface $teamRepository,
    ) {
    }

    public function __invoke(GetTeamQuery $query): TeamOutput
    {
        $team = $this->teamRepository->findById(Uuid::fromString($query->teamId));
        if ($team === null) {
            throw NotFoundException::forEntity('Team', $query->teamId);
        }

        return TeamOutput::fromEntity($team);
    }
}
