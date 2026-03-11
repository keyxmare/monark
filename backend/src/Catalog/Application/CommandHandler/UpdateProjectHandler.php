<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\UpdateProjectCommand;
use App\Catalog\Application\DTO\ProjectOutput;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateProjectHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
    ) {
    }

    public function __invoke(UpdateProjectCommand $command): ProjectOutput
    {
        $project = $this->projectRepository->findById(Uuid::fromString($command->projectId));
        if ($project === null) {
            throw NotFoundException::forEntity('Project', $command->projectId);
        }

        $input = $command->input;

        $project->update(
            name: $input->name,
            slug: $input->slug,
            description: $input->description,
            repositoryUrl: $input->repositoryUrl,
            defaultBranch: $input->defaultBranch,
            visibility: $input->visibility,
        );

        $this->projectRepository->save($project);

        return ProjectOutput::fromEntity($project);
    }
}
