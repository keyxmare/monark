<?php

declare(strict_types=1);

namespace App\History\Presentation\Controller;

use App\History\Application\Command\BackfillProjectHistoryCommand;
use App\Shared\Application\DTO\ApiResponse;
use DateTimeImmutable;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/api/v1/history/projects/{projectId}/backfill', name: 'history_backfill', methods: ['POST'])]
#[OA\Post(
    summary: 'Backfill historical debt snapshots for a project',
    tags: ['History'],
    parameters: [
        new OA\Parameter(name: 'projectId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'since', type: 'string', format: 'date-time'),
                new OA\Property(property: 'until', type: 'string', format: 'date-time'),
                new OA\Property(property: 'intervalDays', type: 'integer', default: 30),
            ],
        ),
    ),
    responses: [new OA\Response(response: 202, description: 'Backfill scheduled')],
)]
final readonly class BackfillProjectHistoryController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(string $projectId, Request $request): JsonResponse
    {
        /** @var array{since?: string, until?: string, intervalDays?: int} $payload */
        $payload = (array) \json_decode((string) $request->getContent(), true);

        $since = $this->parseDate($payload['since'] ?? null);
        $until = $this->parseDate($payload['until'] ?? null);
        if ($since === null || $until === null) {
            return new JsonResponse(
                ApiResponse::error('since and until are required ISO-8601 dates', 400)->toArray(),
                400,
            );
        }

        $intervalDays = (int) ($payload['intervalDays'] ?? 30);
        if ($intervalDays < 1) {
            $intervalDays = 30;
        }

        $this->commandBus->dispatch(new BackfillProjectHistoryCommand(
            projectId: $projectId,
            since: $since,
            until: $until,
            intervalDays: $intervalDays,
        ));

        return new JsonResponse(
            ApiResponse::success(['scheduled' => true])->toArray(),
            202,
        );
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
