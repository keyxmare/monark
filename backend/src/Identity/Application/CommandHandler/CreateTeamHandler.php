<?php

declare(strict_types=1);

namespace App\Identity\Application\CommandHandler;

use App\Identity\Application\Command\CreateTeamCommand;
use App\Identity\Application\DTO\TeamOutput;
use App\Identity\Domain\Model\Team;
use App\Identity\Domain\Repository\TeamRepositoryInterface;
use App\Shared\Domain\Exception\DomainException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateTeamHandler
{
    public function __construct(
        private TeamRepositoryInterface $teamRepository,
    ) {
    }

    public function __invoke(CreateTeamCommand $command): TeamOutput
    {
        $input = $command->input;

        $existing = $this->teamRepository->findBySlug($input->slug);
        if ($existing !== null) {
            throw new class ('A team with this slug already exists.') extends DomainException {};
        }

        $team = Team::create(
            name: $input->name,
            slug: $input->slug,
            description: $input->description,
        );

        $this->teamRepository->save($team);

        return TeamOutput::fromEntity($team);
    }
}
