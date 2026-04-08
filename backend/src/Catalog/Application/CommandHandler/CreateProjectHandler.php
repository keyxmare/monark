<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\CreateProjectCommand;
use App\Catalog\Application\DTO\ProjectOutput;
use App\Catalog\Application\Mapper\ProjectMapper;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\DomainException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateProjectHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        #[Autowire(service: 'cache.query')]
        private TagAwareCacheInterface $cache,
    ) {
    }

    public function __invoke(CreateProjectCommand $command): ProjectOutput
    {
        $input = $command->input;

        $existing = $this->projectRepository->findBySlug($input->slug);
        if ($existing !== null) {
            throw new class ('A project with this slug already exists.') extends DomainException {};
        }

        $project = Project::create(
            name: $input->name,
            slug: $input->slug,
            description: $input->description,
            repositoryUrl: $input->repositoryUrl,
            defaultBranch: $input->defaultBranch,
            visibility: $input->visibility,
            ownerId: Uuid::fromString($input->ownerId),
        );

        $this->projectRepository->save($project);

        $this->cache->invalidateTags(['projects']);

        return ProjectMapper::toOutput($project);
    }
}
