<?php

declare(strict_types=1);

namespace App\Dependency\Presentation\Controller;

use App\Dependency\Application\DTO\DependencyListOutput;
use App\Dependency\Application\Query\ListDependenciesQuery;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dependency/dependencies', name: 'dependency_dependencies_list', methods: ['GET'])]
final readonly class ListDependenciesController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $perPage = $request->query->getInt('per_page', 20);
        $projectId = $request->query->get('project_id');
        $search = $request->query->get('search');
        $packageManager = $request->query->get('package_manager');
        $type = $request->query->get('type');
        $sort = $request->query->get('sort', 'name');
        $sortDir = $request->query->get('sort_dir', 'asc');

        $isOutdatedParam = $request->query->get('is_outdated');
        $isOutdated = $isOutdatedParam !== null ? $isOutdatedParam === '1' || $isOutdatedParam === 'true' : null;

        $envelope = $this->queryBus->dispatch(new ListDependenciesQuery(
            page: $page,
            perPage: $perPage,
            projectId: $projectId,
            search: $search,
            packageManager: $packageManager,
            type: $type,
            isOutdated: $isOutdated,
            sort: $sort,
            sortDir: $sortDir,
        ));

        /** @var DependencyListOutput $result */
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result->pagination->toArray())->toArray());
    }
}
