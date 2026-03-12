<?php

declare(strict_types=1);

namespace App\Catalog\Application\QueryHandler;

use App\Catalog\Application\DTO\RemoteProjectListOutput;
use App\Catalog\Application\DTO\RemoteProjectOutput;
use App\Catalog\Application\Query\ListRemoteProjectsQuery;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Catalog\Domain\Repository\ProviderRepositoryInterface;
use App\Catalog\Infrastructure\GitProvider\GitProviderFactory;
use App\Shared\Application\DTO\PaginatedOutput;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListRemoteProjectsHandler
{
    public function __construct(
        private ProviderRepositoryInterface $providerRepository,
        private ProjectRepositoryInterface $projectRepository,
        private GitProviderFactory $gitProviderFactory,
    ) {
    }

    public function __invoke(ListRemoteProjectsQuery $query): RemoteProjectListOutput
    {
        $provider = $this->providerRepository->findById(Uuid::fromString($query->providerId));
        if ($provider === null) {
            throw NotFoundException::forEntity('Provider', $query->providerId);
        }

        $client = $this->gitProviderFactory->create($provider);
        $remoteProjects = $client->listProjects($provider, $query->page, $query->perPage);
        $total = $client->countProjects($provider);

        $importedExternalIds = $this->projectRepository->findExternalIdsByProvider($provider->getId());
        $importedSet = \array_flip($importedExternalIds);

        $items = \array_map(
            static fn ($remote) => RemoteProjectOutput::fromRemoteProject(
                $remote,
                isset($importedSet[$remote->externalId]),
            ),
            $remoteProjects,
        );

        return new RemoteProjectListOutput(
            pagination: new PaginatedOutput(
                items: $items,
                total: $total,
                page: $query->page,
                perPage: $query->perPage,
            ),
        );
    }
}
