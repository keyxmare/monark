<?php

declare(strict_types=1);

namespace App\History\Presentation\Controller;

use App\History\Application\DTO\DebtTimelinePoint;
use App\History\Application\Query\GetDebtTimelineQuery;
use App\Shared\Application\DTO\ApiResponse;
use DateTimeImmutable;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/api/v1/history/projects/{projectId}/timeline', name: 'history_debt_timeline', methods: ['GET'])]
#[OA\Get(
    summary: 'Get the debt timeline of a project',
    tags: ['History'],
    parameters: [
        new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')),
        new OA\Parameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')),
    ],
    responses: [new OA\Response(response: 200, description: 'Ordered list of debt snapshots')],
)]
final readonly class GetDebtTimelineController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(string $projectId, Request $request): JsonResponse
    {
        $from = $this->parseDate($request->query->get('from'));
        $to = $this->parseDate($request->query->get('to'));

        $envelope = $this->queryBus->dispatch(new GetDebtTimelineQuery(
            projectId: $projectId,
            from: $from,
            to: $to,
        ));

        /** @var list<DebtTimelinePoint> $points */
        $points = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return new JsonResponse(ApiResponse::success(\array_map(
            static fn (DebtTimelinePoint $p): array => $p->toArray(),
            $points,
        ))->toArray());
    }

    private function parseDate(?string $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return new DateTimeImmutable($value);
        } catch (Throwable) {
            return null;
        }
    }
}
