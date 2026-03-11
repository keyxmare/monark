<?php

declare(strict_types=1);

use App\Identity\Application\Command\DeleteAccessTokenCommand;
use App\Identity\Application\CommandHandler\DeleteAccessTokenHandler;
use App\Identity\Domain\Model\AccessToken;
use App\Identity\Domain\Model\TokenProvider;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\AccessTokenRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubDeleteTokenRepo(?AccessToken $token = null): AccessTokenRepositoryInterface
{
    return new class ($token) implements AccessTokenRepositoryInterface {
        public bool $deleted = false;
        public function __construct(private readonly ?AccessToken $token) {}
        public function findById(Uuid $id): ?AccessToken { return $this->token; }
        public function findByUser(Uuid $userId, int $page = 1, int $perPage = 20): array { return []; }
        public function countByUser(Uuid $userId): int { return 0; }
        public function save(AccessToken $accessToken): void {}
        public function delete(AccessToken $accessToken): void { $this->deleted = true; }
    };
}

describe('DeleteAccessTokenHandler', function () {
    it('deletes an access token successfully', function () {
        $user = User::create(
            email: 'john@example.com',
            hashedPassword: 'hashed',
            firstName: 'John',
            lastName: 'Doe',
        );

        $token = AccessToken::create(
            provider: TokenProvider::Gitlab,
            token: 'test-token',
            scopes: [],
            expiresAt: null,
            user: $user,
        );

        $repo = stubDeleteTokenRepo($token);
        $handler = new DeleteAccessTokenHandler($repo);

        $handler(new DeleteAccessTokenCommand($token->getId()->toRfc4122()));

        expect($repo->deleted)->toBeTrue();
    });

    it('throws not found when token does not exist', function () {
        $handler = new DeleteAccessTokenHandler(stubDeleteTokenRepo(null));
        $handler(new DeleteAccessTokenCommand('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
