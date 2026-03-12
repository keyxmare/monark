<?php

declare(strict_types=1);

namespace App\Dependency\Presentation\Controller;

use App\Dependency\Application\DTO\VulnerabilityListOutput;
use App\Dependency\Application\Query\ListVulnerabilitiesQuery;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dependency/vulnerabilities', name: 'dependency_vulnerabilities_list', methods: ['GET'])]
final readonly class ListVulnerabilitiesController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $perPage = $request->query->getInt('per_page', 20);

        $envelope = $this->queryBus->dispatch(new ListVulnerabilitiesQuery($page, $perPage));
        /** @var VulnerabilityListOutput $result */
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result->pagination->toArray())->toArray());
    }
}
