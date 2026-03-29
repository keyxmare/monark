<?php

declare(strict_types=1);

use App\Identity\Domain\Model\AccessToken;
use App\Identity\Domain\Model\TokenProvider;
use App\Tests\Factory\Identity\AccessTokenFactory;
use App\Tests\Factory\Identity\UserFactory;
use Symfony\Component\Uid\Uuid;

describe('AccessToken', function () {
    it('creates with default values via factory', function () {
        $user = UserFactory::create();
        $token = AccessTokenFactory::create($user);

        expect($token)->toBeInstanceOf(AccessToken::class);
        expect($token->getId())->toBeInstanceOf(Uuid::class);
        expect($token->getProvider())->toBe(TokenProvider::Gitlab);
        expect($token->getToken())->toBe('glpat-test-token-123');
        expect($token->getScopes())->toBe(['read_api']);
        expect($token->getExpiresAt())->toBeNull();
        expect($token->getUser())->toBe($user);
        expect($token->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('creates with custom provider and scopes', function () {
        $user = UserFactory::create();
        $token = AccessTokenFactory::create($user, [
            'provider' => TokenProvider::Github,
            'token' => 'ghp_custom_token',
            'scopes' => ['repo', 'read:org'],
        ]);

        expect($token->getProvider())->toBe(TokenProvider::Github);
        expect($token->getToken())->toBe('ghp_custom_token');
        expect($token->getScopes())->toBe(['repo', 'read:org']);
    });

    it('creates with expiration date', function () {
        $user = UserFactory::create();
        $expiresAt = new \DateTimeImmutable('+30 days');
        $token = AccessTokenFactory::create($user, [
            'expiresAt' => $expiresAt,
        ]);

        expect($token->getExpiresAt())->toBe($expiresAt);
    });

    it('is not expired when expiresAt is null', function () {
        $user = UserFactory::create();
        $token = AccessTokenFactory::create($user);

        expect($token->isExpired())->toBeFalse();
    });

    it('is not expired when expiresAt is in the future', function () {
        $user = UserFactory::create();
        $token = AccessTokenFactory::create($user, [
            'expiresAt' => new \DateTimeImmutable('+1 hour'),
        ]);

        expect($token->isExpired())->toBeFalse();
    });

    it('is expired when expiresAt is in the past', function () {
        $user = UserFactory::create();
        $token = AccessTokenFactory::create($user, [
            'expiresAt' => new \DateTimeImmutable('-1 hour'),
        ]);

        expect($token->isExpired())->toBeTrue();
    });

    it('has a UUID v7 id', function () {
        $user = UserFactory::create();
        $token = AccessTokenFactory::create($user);

        expect($token->getId())->toBeInstanceOf(Uuid::class);
    });

    it('returns the associated user', function () {
        $user = UserFactory::create(['email' => 'token-owner@example.com']);
        $token = AccessTokenFactory::create($user);

        expect($token->getUser()->getEmail())->toBe('token-owner@example.com');
    });
});
