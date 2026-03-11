<?php

declare(strict_types=1);

namespace App\Identity\Application\CommandHandler;

use App\Identity\Application\Command\UpdateTeamCommand;
use App\Identity\Application\DTO\TeamOutput;
use App\Identity\Domain\Repository\TeamRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateTeamHandler
{
    public function __construct(
        private TeamRepositoryInterface $teamRepository,
    ) {
    }

    public function __invoke(UpdateTeamCommand $command): TeamOutput
    {
        $team = $this->teamRepository->findById(Uuid::fromString($command->teamId));
        if ($team === null) {
            throw NotFoundException::forEntity('Team', $command->teamId);
        }

        $input = $command->input;

        $team->update(
            name: $input->name,
            slug: $input->slug,
            description: $input->description,
        );

        $this->teamRepository->save($team);

        return TeamOutput::fromEntity($team);
    }
}
