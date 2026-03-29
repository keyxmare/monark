<?php

declare(strict_types=1);

namespace App\Activity\Presentation\Controller;

use App\Activity\Application\Query\GetDashboardQuery;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/activity/dashboard', name: 'activity_dashboard', methods: ['GET'])]
#[OA\Get(
    summary: 'Get the dashboard overview',
    tags: ['Activity / Dashboard'],
    responses: [new OA\Response(response: 200, description: 'Dashboard data')],
)]
final readonly class GetDashboardController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(#[CurrentUser] UserInterface $user): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(
            new GetDashboardQuery($user->getUserIdentifier()),
        );
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray());
    }
}
