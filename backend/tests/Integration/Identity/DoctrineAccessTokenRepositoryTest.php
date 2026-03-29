<?php

declare(strict_types=1);

use App\Identity\Domain\Model\AccessToken;
use App\Identity\Domain\Model\TokenProvider;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\AccessTokenRepositoryInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(AccessTokenRepositoryInterface::class);
    $this->userRepo = self::getContainer()->get(UserRepositoryInterface::class);

    $this->user = User::create('owner@test.com', 'hashed', 'Owner', 'User');
    $this->userRepo->save($this->user);
});

describe('DoctrineAccessTokenRepository', function () {
    it('saves and finds a token by id', function () {
        $token = AccessToken::create(TokenProvider::Gitlab, 'glpat-xxx', ['api'], null, $this->user);
        $this->repo->save($token);

        $found = $this->repo->findById($token->getId());

        expect($found)->not->toBeNull();
        expect($found->getToken())->toBe('glpat-xxx');
    });

    it('finds tokens by user with pagination', function () {
        for ($i = 0; $i < 3; $i++) {
            $this->repo->save(
                AccessToken::create(TokenProvider::Gitlab, "token-{$i}", ['api'], null, $this->user)
            );
        }

        $tokens = $this->repo->findByUser($this->user->getId(), page: 1, perPage: 2);

        expect($tokens)->toHaveCount(2);
    });

    it('counts tokens by user', function () {
        $this->repo->save(AccessToken::create(TokenProvider::Gitlab, 'tok1', ['api'], null, $this->user));
        $this->repo->save(AccessToken::create(TokenProvider::Github, 'tok2', ['repo'], null, $this->user));

        expect($this->repo->countByUser($this->user->getId()))->toBe(2);
    });

    it('deletes a token', function () {
        $token = AccessToken::create(TokenProvider::Gitlab, 'to-delete', ['api'], null, $this->user);
        $this->repo->save($token);

        $this->repo->delete($token);
        $this->getEntityManager()->clear();

        expect($this->repo->findById($token->getId()))->toBeNull();
    });

    it('returns null for unknown id', function () {
        expect($this->repo->findById(\Symfony\Component\Uid\Uuid::v7()))->toBeNull();
    });
});
