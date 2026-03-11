<?php

declare(strict_types=1);

namespace App\Dependency\Application\CommandHandler;

use App\Dependency\Application\Command\UpdateDependencyCommand;
use App\Dependency\Application\DTO\DependencyOutput;
use App\Dependency\Domain\Model\DependencyType;
use App\Dependency\Domain\Model\PackageManager;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateDependencyHandler
{
    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
    ) {
    }

    public function __invoke(UpdateDependencyCommand $command): DependencyOutput
    {
        $dependency = $this->dependencyRepository->findById(Uuid::fromString($command->dependencyId));
        if ($dependency === null) {
            throw NotFoundException::forEntity('Dependency', $command->dependencyId);
        }

        $input = $command->input;

        $dependency->update(
            name: $input->name,
            currentVersion: $input->currentVersion,
            latestVersion: $input->latestVersion,
            ltsVersion: $input->ltsVersion,
            packageManager: $input->packageManager !== null ? PackageManager::from($input->packageManager) : null,
            type: $input->type !== null ? DependencyType::from($input->type) : null,
            isOutdated: $input->isOutdated,
            repositoryUrl: $input->repositoryUrl,
        );

        $this->dependencyRepository->save($dependency);

        return DependencyOutput::fromEntity($dependency);
    }
}
