<?php

declare(strict_types=1);

namespace App\Activity\Infrastructure\Controller;

use App\Activity\Application\Query\GetDashboardQuery;
use App\Identity\Domain\Model\User;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/activity/dashboard', name: 'activity_dashboard', methods: ['GET'])]
final readonly class GetDashboardController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(
            new GetDashboardQuery($user->getId()->toRfc4122()),
        );
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray());
    }
}
