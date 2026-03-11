<?php

declare(strict_types=1);

namespace App\Identity\Application\CommandHandler;

use App\Identity\Application\Command\DeleteTeamCommand;
use App\Identity\Domain\Repository\TeamRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeleteTeamHandler
{
    public function __construct(
        private TeamRepositoryInterface $teamRepository,
    ) {
    }

    public function __invoke(DeleteTeamCommand $command): void
    {
        $team = $this->teamRepository->findById(Uuid::fromString($command->teamId));
        if ($team === null) {
            throw NotFoundException::forEntity('Team', $command->teamId);
        }

        $this->teamRepository->delete($team);
    }
}
