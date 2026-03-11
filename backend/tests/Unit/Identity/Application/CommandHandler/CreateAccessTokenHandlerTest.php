<?php

declare(strict_types=1);

use App\Identity\Application\Command\CreateAccessTokenCommand;
use App\Identity\Application\CommandHandler\CreateAccessTokenHandler;
use App\Identity\Application\DTO\AccessTokenOutput;
use App\Identity\Application\DTO\CreateAccessTokenInput;
use App\Identity\Domain\Model\AccessToken;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\AccessTokenRepositoryInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubAccessTokenRepo(): AccessTokenRepositoryInterface
{
    return new class implements AccessTokenRepositoryInterface {
        public ?AccessToken $saved = null;
        public function findById(Uuid $id): ?AccessToken { return null; }
        public function findByUser(Uuid $userId, int $page = 1, int $perPage = 20): array { return []; }
        public function countByUser(Uuid $userId): int { return 0; }
        public function save(AccessToken $accessToken): void { $this->saved = $accessToken; }
        public function delete(AccessToken $accessToken): void {}
    };
}

function stubUserRepoForToken(?User $user = null): UserRepositoryInterface
{
    return new class ($user) implements UserRepositoryInterface {
        public function __construct(private readonly ?User $user) {}
        public function findById(Uuid $id): ?User { return $this->user; }
        public function findByEmail(string $email): ?User { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function save(User $user): void {}
    };
}

describe('CreateAccessTokenHandler', function () {
    it('creates an access token successfully', function () {
        $user = User::create(
            email: 'john@example.com',
            hashedPassword: 'hashed',
            firstName: 'John',
            lastName: 'Doe',
        );

        $userId = $user->getId()->toRfc4122();
        $tokenRepo = stubAccessTokenRepo();
        $handler = new CreateAccessTokenHandler($tokenRepo, stubUserRepoForToken($user));

        $input = new CreateAccessTokenInput(
            provider: 'gitlab',
            token: 'glpat-test-token',
            scopes: ['read_api', 'read_repository'],
        );

        $result = $handler(new CreateAccessTokenCommand($userId, $input));

        expect($result)->toBeInstanceOf(AccessTokenOutput::class);
        expect($result->provider)->toBe('gitlab');
        expect($result->scopes)->toBe(['read_api', 'read_repository']);
        expect($result->userId)->toBe($userId);
        expect($tokenRepo->saved)->not->toBeNull();
    });

    it('throws not found when user does not exist', function () {
        $handler = new CreateAccessTokenHandler(
            stubAccessTokenRepo(),
            stubUserRepoForToken(null),
        );

        $input = new CreateAccessTokenInput(provider: 'gitlab', token: 'glpat-test', scopes: []);
        $handler(new CreateAccessTokenCommand('00000000-0000-0000-0000-000000000000', $input));
    })->throws(NotFoundException::class);
});
