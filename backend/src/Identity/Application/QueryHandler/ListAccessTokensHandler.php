<?php

declare(strict_types=1);

namespace App\Identity\Application\QueryHandler;

use App\Identity\Application\DTO\AccessTokenListOutput;
use App\Identity\Application\DTO\AccessTokenOutput;
use App\Identity\Application\Query\ListAccessTokensQuery;
use App\Identity\Domain\Repository\AccessTokenRepositoryInterface;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListAccessTokensHandler
{
    public function __construct(
        private AccessTokenRepositoryInterface $accessTokenRepository,
    ) {
    }

    public function __invoke(ListAccessTokensQuery $query): AccessTokenListOutput
    {
        $userId = Uuid::fromString($query->userId);
        $tokens = $this->accessTokenRepository->findByUser($userId, $query->page, $query->perPage);
        $total = $this->accessTokenRepository->countByUser($userId);

        $items = \array_map(
            static fn ($token) => AccessTokenOutput::fromEntity($token),
            $tokens,
        );

        return new AccessTokenListOutput(
            pagination: new PaginatedOutput(
                items: $items,
                total: $total,
                page: $query->page,
                perPage: $query->perPage,
            ),
        );
    }
}
