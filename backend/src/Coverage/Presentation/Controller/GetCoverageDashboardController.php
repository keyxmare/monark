<?php

declare(strict_types=1);

namespace App\Coverage\Presentation\Controller;

use App\Coverage\Application\DTO\CoverageDashboardOutput;
use App\Coverage\Application\Query\GetCoverageDashboardQuery;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/coverage', name: 'coverage_dashboard_get', methods: ['GET'])]
#[OA\Get(
    summary: 'Get coverage dashboard',
    tags: ['Coverage'],
    responses: [
        new OA\Response(response: 200, description: 'Coverage summary and per-project data'),
    ],
)]
final readonly class GetCoverageDashboardController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetCoverageDashboardQuery());

        /** @var CoverageDashboardOutput $result */
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success([
            'summary' => [
                'averageCoverage' => $result->summary->averageCoverage,
                'totalProjects' => $result->summary->totalProjects,
                'coveredProjects' => $result->summary->coveredProjects,
                'aboveThreshold' => $result->summary->aboveThreshold,
                'belowThreshold' => $result->summary->belowThreshold,
                'trend' => $result->summary->trend,
            ],
            'projects' => \array_map(
                static fn ($p) => [
                    'projectId' => $p->projectId,
                    'projectName' => $p->projectName,
                    'projectSlug' => $p->projectSlug,
                    'coveragePercent' => $p->coveragePercent,
                    'trend' => $p->trend,
                    'source' => $p->source,
                    'commitHash' => $p->commitHash,
                    'ref' => $p->ref,
                    'syncedAt' => $p->syncedAt,
                ],
                $result->projects,
            ),
        ])->toArray());
    }
}
