<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Controller;

use App\Identity\Application\DTO\AccessTokenListOutput;
use App\Identity\Application\Query\ListAccessTokensQuery;
use App\Identity\Domain\Model\User;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/v1/identity/access-tokens', name: 'identity_access_tokens_list', methods: ['GET'])]
#[OA\Get(
    summary: 'List access tokens for the current user',
    tags: ['Identity / Access Tokens'],
    parameters: [
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
    ],
    responses: [new OA\Response(response: 200, description: 'Paginated list of access tokens')],
)]
final readonly class ListAccessTokensController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $perPage = $request->query->getInt('per_page', 20);

        $envelope = $this->queryBus->dispatch(
            new ListAccessTokensQuery($user->getId()->toRfc4122(), $page, $perPage),
        );
        /** @var AccessTokenListOutput $result */
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result->pagination->toArray())->toArray());
    }
}
