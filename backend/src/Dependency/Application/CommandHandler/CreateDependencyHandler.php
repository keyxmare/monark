<?php

declare(strict_types=1);

namespace App\Dependency\Application\CommandHandler;

use App\Dependency\Application\Command\CreateDependencyCommand;
use App\Dependency\Application\DTO\DependencyOutput;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateDependencyHandler
{
    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
    ) {
    }

    public function __invoke(CreateDependencyCommand $command): DependencyOutput
    {
        $input = $command->input;

        $dependency = Dependency::create(
            name: $input->name,
            currentVersion: $input->currentVersion,
            latestVersion: $input->latestVersion,
            ltsVersion: $input->ltsVersion,
            packageManager: PackageManager::from($input->packageManager),
            type: DependencyType::from($input->type),
            isOutdated: $input->isOutdated,
            projectId: Uuid::fromString($input->projectId),
            repositoryUrl: $input->repositoryUrl,
        );

        $this->dependencyRepository->save($dependency);

        return DependencyOutput::fromEntity($dependency);
    }
}
