<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use SensitiveParameter;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Uid\Uuid;

final readonly class ApiTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private string $appSecret,
    ) {
    }

    public function createToken(string $userId): string
    {
        $expiresAt = (new DateTimeImmutable('+24 hours'))->getTimestamp();
        $payload = $userId . '|' . $expiresAt;
        $signature = \hash_hmac('sha256', $payload, $this->appSecret);

        return \base64_encode($payload . '|' . $signature);
    }

    public function getUserBadgeFrom(#[SensitiveParameter] string $accessToken): UserBadge
    {
        $decoded = \base64_decode($accessToken, true);
        if ($decoded === false) {
            throw new BadCredentialsException('Invalid token format.');
        }

        $parts = \explode('|', $decoded);
        if (\count($parts) !== 3) {
            throw new BadCredentialsException('Invalid token structure.');
        }

        [$userId, $expiresAt, $signature] = $parts;

        $expectedSignature = \hash_hmac('sha256', $userId . '|' . $expiresAt, $this->appSecret);
        if (!\hash_equals($expectedSignature, $signature)) {
            throw new BadCredentialsException('Invalid token signature.');
        }

        if ((int) $expiresAt < \time()) {
            throw new BadCredentialsException('Token has expired.');
        }

        return new UserBadge($userId, function (string $id) {
            $user = $this->userRepository->findById(Uuid::fromRfc4122($id));
            if ($user === null) {
                throw new BadCredentialsException('User not found.');
            }

            return $user;
        });
    }
}
