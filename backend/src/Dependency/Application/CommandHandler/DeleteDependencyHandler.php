<?php

declare(strict_types=1);

namespace App\Dependency\Application\CommandHandler;

use App\Dependency\Application\Command\DeleteDependencyCommand;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeleteDependencyHandler
{
    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
        #[Autowire(service: 'cache.query')]
        private TagAwareCacheInterface $cache,
    ) {
    }

    public function __invoke(DeleteDependencyCommand $command): void
    {
        $dependency = $this->dependencyRepository->findById(Uuid::fromString($command->dependencyId));
        if ($dependency === null) {
            throw NotFoundException::forEntity('Dependency', $command->dependencyId);
        }

        $this->dependencyRepository->delete($dependency);

        $this->cache->invalidateTags(['dependencies']);
    }
}
