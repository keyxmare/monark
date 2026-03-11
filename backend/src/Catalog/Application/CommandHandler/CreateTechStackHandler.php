<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\CreateTechStackCommand;
use App\Catalog\Application\DTO\TechStackOutput;
use App\Catalog\Domain\Model\TechStack;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateTechStackHandler
{
    public function __construct(
        private TechStackRepositoryInterface $techStackRepository,
        private ProjectRepositoryInterface $projectRepository,
    ) {
    }

    public function __invoke(CreateTechStackCommand $command): TechStackOutput
    {
        $input = $command->input;

        $project = $this->projectRepository->findById(Uuid::fromString($input->projectId));
        if ($project === null) {
            throw NotFoundException::forEntity('Project', $input->projectId);
        }

        $techStack = TechStack::create(
            language: $input->language,
            framework: $input->framework,
            version: $input->version,
            frameworkVersion: $input->frameworkVersion,
            detectedAt: new \DateTimeImmutable($input->detectedAt),
            project: $project,
        );

        $this->techStackRepository->save($techStack);

        return TechStackOutput::fromEntity($techStack);
    }
}
