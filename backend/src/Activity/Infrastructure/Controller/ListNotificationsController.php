<?php

declare(strict_types=1);

namespace App\Activity\Infrastructure\Controller;

use App\Activity\Application\DTO\NotificationListOutput;
use App\Activity\Application\Query\ListNotificationsQuery;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/activity/notifications', name: 'activity_notifications_list', methods: ['GET'])]
final readonly class ListNotificationsController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(#[CurrentUser] UserInterface $user, Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $perPage = $request->query->getInt('per_page', 20);

        $envelope = $this->queryBus->dispatch(
            new ListNotificationsQuery($user->getUserIdentifier(), $page, $perPage),
        );
        /** @var NotificationListOutput $result */
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result->pagination->toArray())->toArray());
    }
}
