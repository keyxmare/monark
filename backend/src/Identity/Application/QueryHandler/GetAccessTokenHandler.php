<?php

declare(strict_types=1);

namespace App\Identity\Application\QueryHandler;

use App\Identity\Application\DTO\AccessTokenOutput;
use App\Identity\Application\Query\GetAccessTokenQuery;
use App\Identity\Domain\Repository\AccessTokenRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetAccessTokenHandler
{
    public function __construct(
        private AccessTokenRepositoryInterface $accessTokenRepository,
    ) {
    }

    public function __invoke(GetAccessTokenQuery $query): AccessTokenOutput
    {
        $token = $this->accessTokenRepository->findById(Uuid::fromString($query->tokenId));
        if ($token === null) {
            throw NotFoundException::forEntity('AccessToken', $query->tokenId);
        }

        return AccessTokenOutput::fromEntity($token);
    }
}
