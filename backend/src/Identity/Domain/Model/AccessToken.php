<?php

declare(strict_types=1);

namespace App\Identity\Domain\Model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'identity_access_tokens')]
final class AccessToken
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'string', enumType: TokenProvider::class)]
    private TokenProvider $provider;

    #[ORM\Column(type: 'text')]
    private string $token;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $scopes;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'accessTokens')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    private function __construct(
        Uuid $id,
        TokenProvider $provider,
        string $token,
        array $scopes,
        ?\DateTimeImmutable $expiresAt,
        User $user,
    ) {
        $this->id = $id;
        $this->provider = $provider;
        $this->token = $token;
        $this->scopes = $scopes;
        $this->expiresAt = $expiresAt;
        $this->user = $user;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function create(
        TokenProvider $provider,
        string $token,
        array $scopes,
        ?\DateTimeImmutable $expiresAt,
        User $user,
    ): self {
        return new self(
            id: Uuid::v7(),
            provider: $provider,
            token: $token,
            scopes: $scopes,
            expiresAt: $expiresAt,
            user: $user,
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getProvider(): TokenProvider
    {
        return $this->provider;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    /** @return list<string> */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $this->expiresAt < new \DateTimeImmutable();
    }
}
