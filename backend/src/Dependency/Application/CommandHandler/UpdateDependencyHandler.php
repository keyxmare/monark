<?php

declare(strict_types=1);

namespace App\Dependency\Application\CommandHandler;

use App\Dependency\Application\Command\UpdateDependencyCommand;
use App\Dependency\Application\DTO\DependencyOutput;
use App\Dependency\Application\Mapper\DependencyMapper;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateDependencyHandler
{
    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
        #[Autowire(service: 'cache.query')]
        private TagAwareCacheInterface $cache,
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

        $this->cache->invalidateTags(['dependencies']);

        return DependencyMapper::toOutput($dependency);
    }
}
