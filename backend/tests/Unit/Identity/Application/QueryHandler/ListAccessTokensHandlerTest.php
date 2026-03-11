<?php

declare(strict_types=1);

use App\Identity\Application\DTO\AccessTokenListOutput;
use App\Identity\Application\Query\ListAccessTokensQuery;
use App\Identity\Application\QueryHandler\ListAccessTokensHandler;
use App\Identity\Domain\Model\AccessToken;
use App\Identity\Domain\Model\TokenProvider;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\AccessTokenRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubListTokensRepo(array $tokens = [], int $count = 0): AccessTokenRepositoryInterface
{
    return new class ($tokens, $count) implements AccessTokenRepositoryInterface {
        public function __construct(private readonly array $tokens, private readonly int $count) {}
        public function findById(Uuid $id): ?AccessToken { return null; }
        public function findByUser(Uuid $userId, int $page = 1, int $perPage = 20): array { return $this->tokens; }
        public function countByUser(Uuid $userId): int { return $this->count; }
        public function save(AccessToken $accessToken): void {}
        public function delete(AccessToken $accessToken): void {}
    };
}

describe('ListAccessTokensHandler', function () {
    it('returns paginated access tokens for a user', function () {
        $user = User::create(email: 'john@example.com', hashedPassword: 'h', firstName: 'John', lastName: 'Doe');
        $token = AccessToken::create(
            provider: TokenProvider::Gitlab,
            token: 'test-token',
            scopes: ['read_api'],
            expiresAt: null,
            user: $user,
        );

        $handler = new ListAccessTokensHandler(stubListTokensRepo([$token], 1));
        $result = $handler(new ListAccessTokensQuery($user->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(AccessTokenListOutput::class);
        expect($result->pagination->items)->toHaveCount(1);
        expect($result->pagination->total)->toBe(1);
    });
});
