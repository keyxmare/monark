<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Application\CommandHandler;

use App\Hub\Shared\Domain\ValueObject\DependencyType;
use App\Hub\Shared\Domain\ValueObject\PackageManager;
use App\Monitoring\Dependency\Application\Command\CreateDependencyCommand;
use App\Monitoring\Dependency\Application\DTO\DependencyOutput;
use App\Monitoring\Dependency\Application\Mapper\DependencyMapper;
use App\Monitoring\Dependency\Domain\Model\Dependency;
use App\Monitoring\Dependency\Domain\Repository\DependencyRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateDependencyHandler
{
    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
        #[Autowire(service: 'cache.query')]
        private TagAwareCacheInterface $cache,
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

        $this->cache->invalidateTags(['dependencies']);

        return DependencyMapper::toOutput($dependency);
    }
}
