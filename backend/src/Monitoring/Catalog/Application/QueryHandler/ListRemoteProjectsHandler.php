<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\QueryHandler;

use App\Hub\Shared\Application\DTO\PaginatedOutput;
use App\Hub\Shared\Domain\Exception\NotFoundException;
use App\Monitoring\Catalog\Application\DTO\RemoteProjectListOutput;
use App\Monitoring\Catalog\Application\DTO\RemoteProjectOutput;
use App\Monitoring\Catalog\Application\Query\ListRemoteProjectsQuery;
use App\Monitoring\Catalog\Domain\Model\ProviderStatus;
use App\Monitoring\Catalog\Domain\Port\GitProviderFactoryInterface;
use App\Monitoring\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Monitoring\Catalog\Domain\Repository\ProviderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListRemoteProjectsHandler
{
    public function __construct(
        private ProviderRepositoryInterface $providerRepository,
        private ProjectRepositoryInterface $projectRepository,
        private GitProviderFactoryInterface $gitProviderFactory,
    ) {
    }

    public function __invoke(ListRemoteProjectsQuery $query): RemoteProjectListOutput
    {
        $provider = $this->providerRepository->findById(Uuid::fromString($query->providerId));
        if ($provider === null) {
            throw NotFoundException::forEntity('Provider', $query->providerId);
        }

        $client = $this->gitProviderFactory->create($provider);

        try {
            $remoteProjects = $client->listProjects($provider, $query->page, $query->perPage, $query->search, $query->visibility, $query->sort, $query->sortDir);
            $total = $client->countProjects($provider, $query->search, $query->visibility);
        } catch (ExceptionInterface $e) {
            if ($provider->getStatus() !== ProviderStatus::Error) {
                $provider->markError();
                $this->providerRepository->save($provider);
            }

            throw $e;
        }

        $importedMap = $this->projectRepository->findExternalIdMapByProvider($provider->getId());

        $items = \array_map(
            static fn ($remote) => RemoteProjectOutput::fromRemoteProject(
                $remote,
                isset($importedMap[$remote->externalId]),
                $importedMap[$remote->externalId] ?? null,
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
