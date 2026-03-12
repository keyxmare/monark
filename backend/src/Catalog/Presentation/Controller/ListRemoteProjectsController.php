<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\DTO\RemoteProjectListOutput;
use App\Catalog\Application\Query\ListRemoteProjectsQuery;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/catalog/providers/{id}/remote-projects', name: 'catalog_providers_remote_projects', methods: ['GET'])]
final readonly class ListRemoteProjectsController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(string $id, Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $perPage = $request->query->getInt('per_page', 20);
        $search = $request->query->getString('search') ?: null;
        $visibility = $request->query->getString('visibility') ?: null;
        $sort = $request->query->getString('sort') ?: 'name';
        $sortDir = $request->query->getString('sort_dir') ?: 'asc';

        $envelope = $this->queryBus->dispatch(new ListRemoteProjectsQuery($id, $page, $perPage, $search, $visibility, $sort, $sortDir));
        /** @var RemoteProjectListOutput $result */
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result->pagination->toArray())->toArray());
    }
}
