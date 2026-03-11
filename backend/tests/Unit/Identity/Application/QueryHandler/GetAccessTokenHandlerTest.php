<?php

declare(strict_types=1);

use App\Identity\Application\DTO\AccessTokenOutput;
use App\Identity\Application\Query\GetAccessTokenQuery;
use App\Identity\Application\QueryHandler\GetAccessTokenHandler;
use App\Identity\Domain\Model\AccessToken;
use App\Identity\Domain\Model\TokenProvider;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\AccessTokenRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubGetTokenRepo(?AccessToken $token = null): AccessTokenRepositoryInterface
{
    return new class ($token) implements AccessTokenRepositoryInterface {
        public function __construct(private readonly ?AccessToken $token) {}
        public function findById(Uuid $id): ?AccessToken { return $this->token; }
        public function findByUser(Uuid $userId, int $page = 1, int $perPage = 20): array { return []; }
        public function countByUser(Uuid $userId): int { return 0; }
        public function save(AccessToken $accessToken): void {}
        public function delete(AccessToken $accessToken): void {}
    };
}

describe('GetAccessTokenHandler', function () {
    it('returns an access token by id', function () {
        $user = User::create(email: 'john@example.com', hashedPassword: 'h', firstName: 'John', lastName: 'Doe');
        $token = AccessToken::create(
            provider: TokenProvider::Gitlab,
            token: 'test-token',
            scopes: ['read_api'],
            expiresAt: null,
            user: $user,
        );

        $handler = new GetAccessTokenHandler(stubGetTokenRepo($token));
        $result = $handler(new GetAccessTokenQuery($token->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(AccessTokenOutput::class);
        expect($result->provider)->toBe('gitlab');
        expect($result->scopes)->toBe(['read_api']);
    });

    it('throws not found when token does not exist', function () {
        $handler = new GetAccessTokenHandler(stubGetTokenRepo(null));
        $handler(new GetAccessTokenQuery('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
