<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\ImportProjectsCommand;
use App\Catalog\Application\DTO\ProjectOutput;
use App\Catalog\Application\Mapper\ProjectMapper;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Catalog\Domain\Repository\ProviderRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ImportProjectsHandler
{
    public function __construct(
        private ProviderRepositoryInterface $providerRepository,
        private ProjectRepositoryInterface $projectRepository,
        #[Autowire(service: 'cache.query')]
        private TagAwareCacheInterface $cache,
    ) {
    }

    /** @return list<ProjectOutput> */
    public function __invoke(ImportProjectsCommand $command): array
    {
        $provider = $this->providerRepository->findById(Uuid::fromString($command->providerId));
        if ($provider === null) {
            throw NotFoundException::forEntity('Provider', $command->providerId);
        }

        $input = $command->input;
        $ownerId = Uuid::fromString($command->ownerId);
        $imported = [];

        foreach ($input->projects as $item) {
            $existing = $this->projectRepository->findByExternalIdAndProvider(
                $item->externalId,
                $provider->getId(),
            );

            if ($existing !== null) {
                continue;
            }

            $slug = $this->uniqueSlug($item->slug);

            $project = Project::create(
                name: $item->name,
                slug: $slug,
                description: $item->description,
                repositoryUrl: $item->repositoryUrl,
                defaultBranch: $item->defaultBranch,
                visibility: $this->resolveVisibility($item->visibility),
                ownerId: $ownerId,
                provider: $provider,
                externalId: $item->externalId,
            );

            $this->projectRepository->save($project);
            $imported[] = ProjectMapper::toOutput($project);
        }

        if (\count($imported) > 0) {
            $this->cache->invalidateTags(['projects']);
        }

        return $imported;
    }

    private function resolveVisibility(string $visibility): ProjectVisibility
    {
        return match ($visibility) {
            'public', 'internal' => ProjectVisibility::Public,
            default => ProjectVisibility::Private,
        };
    }

    private function uniqueSlug(string $slug): string
    {
        $normalized = \strtolower(\preg_replace('/[^a-z0-9\-]/', '-', \str_replace('/', '-', $slug)) ?? $slug);
        $normalized = \trim(\preg_replace('/-+/', '-', $normalized) ?? $normalized, '-');

        $candidate = $normalized;
        $suffix = 1;

        while ($this->projectRepository->findBySlug($candidate) !== null) {
            $candidate = $normalized . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }
}
