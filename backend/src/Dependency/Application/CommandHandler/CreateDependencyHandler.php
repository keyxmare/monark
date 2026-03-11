<?php

declare(strict_types=1);

namespace App\Dependency\Application\CommandHandler;

use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Dependency\Application\Command\CreateDependencyCommand;
use App\Dependency\Application\DTO\DependencyOutput;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\DependencyType;
use App\Dependency\Domain\Model\PackageManager;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateDependencyHandler
{
    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
        private ProjectRepositoryInterface $projectRepository,
    ) {
    }

    public function __invoke(CreateDependencyCommand $command): DependencyOutput
    {
        $input = $command->input;

        $project = $this->projectRepository->findById(Uuid::fromString($input->projectId));
        if ($project === null) {
            throw NotFoundException::forEntity('Project', $input->projectId);
        }

        $dependency = Dependency::create(
            name: $input->name,
            currentVersion: $input->currentVersion,
            latestVersion: $input->latestVersion,
            ltsVersion: $input->ltsVersion,
            packageManager: PackageManager::from($input->packageManager),
            type: DependencyType::from($input->type),
            isOutdated: $input->isOutdated,
            project: $project,
        );

        $this->dependencyRepository->save($dependency);

        return DependencyOutput::fromEntity($dependency);
    }
}
