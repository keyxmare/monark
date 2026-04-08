<?php

declare(strict_types=1);

use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Infrastructure\Security\ApiTokenHandler;
use App\Identity\Infrastructure\Security\LoginFailureHandler;
use App\Identity\Infrastructure\Security\LoginSuccessHandler;
use App\Tests\Factory\Identity\UserFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Contracts\Translation\TranslatorInterface;

it('returns 401 on authentication failure', function () {
    $translator = $this->createMock(TranslatorInterface::class);
    $translator->method('trans')->with('error.invalid_credentials')->willReturn('Invalid credentials');

    $handler = new LoginFailureHandler($translator);
    $response = $handler->onAuthenticationFailure(
        Request::create('/api/v1/login', 'POST'),
        new AuthenticationException('Bad credentials'),
    );

    expect($response->getStatusCode())->toBe(401);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['success'])->toBeFalse();
    expect($data['error']['message'])->toBe('Invalid credentials');
});

it('returns 200 with token on authentication success', function () {
    $user = UserFactory::create();
    $userRepository = $this->createMock(UserRepositoryInterface::class);
    $apiTokenHandler = new ApiTokenHandler($userRepository, 'test-secret-key');
    $successHandler = new LoginSuccessHandler($apiTokenHandler);

    $token = $this->createMock(TokenInterface::class);
    $token->method('getUser')->willReturn($user);

    $response = $successHandler->onAuthenticationSuccess(
        Request::create('/api/v1/login', 'POST'),
        $token,
    );

    expect($response->getStatusCode())->toBe(200);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['success'])->toBeTrue();
    expect($data['data'])->toHaveKeys(['token', 'user']);
    expect($data['data']['user']['email'])->toBe('john@example.com');
});

it('creates and validates a token round-trip', function () {
    $user = UserFactory::create();
    $userRepository = $this->createMock(UserRepositoryInterface::class);
    $userRepository->method('findById')->willReturn($user);
    $handler = new ApiTokenHandler($userRepository, 'test-secret-key');

    $token = $handler->createToken($user->getId()->toRfc4122());
    $badge = $handler->getUserBadgeFrom($token);

    expect($badge->getUserIdentifier())->toBe($user->getId()->toRfc4122());
    $loadedUser = ($badge->getUserLoader())($badge->getUserIdentifier());
    expect($loadedUser)->toBe($user);
});

it('rejects invalid base64 token', function () {
    $userRepository = $this->createMock(UserRepositoryInterface::class);
    $handler = new ApiTokenHandler($userRepository, 'test-secret-key');

    $handler->getUserBadgeFrom('not-valid-base64!!!');
})->throws(BadCredentialsException::class, 'Invalid token format.');

it('rejects token with wrong structure', function () {
    $userRepository = $this->createMock(UserRepositoryInterface::class);
    $handler = new ApiTokenHandler($userRepository, 'test-secret-key');

    $handler->getUserBadgeFrom(\base64_encode('only-one-part'));
})->throws(BadCredentialsException::class, 'Invalid token structure.');

it('rejects token with invalid signature', function () {
    $userRepository = $this->createMock(UserRepositoryInterface::class);
    $handler = new ApiTokenHandler($userRepository, 'test-secret-key');

    $payload = 'user-id|' . ((new \DateTimeImmutable('+1 hour'))->getTimestamp());
    $fakeToken = \base64_encode($payload . '|invalid-signature');

    $handler->getUserBadgeFrom($fakeToken);
})->throws(BadCredentialsException::class, 'Invalid token signature.');

it('rejects expired token', function () {
    $userRepository = $this->createMock(UserRepositoryInterface::class);
    $handler = new ApiTokenHandler($userRepository, 'test-secret-key');

    $expiresAt = (new \DateTimeImmutable('-1 hour'))->getTimestamp();
    $payload = 'user-id|' . $expiresAt;
    $signature = \hash_hmac('sha256', $payload, 'test-secret-key');
    $expiredToken = \base64_encode($payload . '|' . $signature);

    $handler->getUserBadgeFrom($expiredToken);
})->throws(BadCredentialsException::class, 'Token has expired.');

it('rejects token when user not found', function () {
    $userRepository = $this->createMock(UserRepositoryInterface::class);
    $userRepository->method('findById')->willReturn(null);
    $handler = new ApiTokenHandler($userRepository, 'test-secret-key');

    $token = $handler->createToken('a0000000-0000-0000-0000-000000000001');
    $badge = $handler->getUserBadgeFrom($token);

    ($badge->getUserLoader())($badge->getUserIdentifier());
})->throws(BadCredentialsException::class, 'User not found.');
