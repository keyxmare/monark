<?php

declare(strict_types=1);

namespace App\Identity\Domain\Model;

use App\Shared\Domain\ValueObject\Email;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'identity_users')]
#[ORM\UniqueConstraint(name: 'uniq_user_email', columns: ['email'])]
final class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'app_email', length: 180, unique: true)]
    private Email $email;

    #[ORM\Column]
    private string $password;

    #[ORM\Column(length: 100)]
    private string $firstName;

    #[ORM\Column(length: 100)]
    private string $lastName;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $roles;

    /** @var Collection<int, AccessToken> */
    #[ORM\OneToMany(targetEntity: AccessToken::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $accessTokens;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    /** @param list<string> $roles */
    private function __construct(
        Uuid $id,
        Email $email,
        string $password,
        string $firstName,
        string $lastName,
        ?string $avatar,
        array $roles,
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->avatar = $avatar;
        $this->roles = $roles;
        $this->accessTokens = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /** @param list<string> $roles */
    public static function create(
        string $email,
        string $hashedPassword,
        string $firstName,
        string $lastName,
        ?string $avatar = null,
        array $roles = ['ROLE_USER'],
    ): self {
        return new self(
            id: Uuid::v7(),
            email: new Email($email),
            password: $hashedPassword,
            firstName: $firstName,
            lastName: $lastName,
            avatar: $avatar,
            roles: $roles,
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email->value();
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return \array_values(\array_unique($roles));
    }

    /** @return Collection<int, AccessToken> */
    public function getAccessTokens(): Collection
    {
        return $this->accessTokens;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return non-empty-string */
    public function getUserIdentifier(): string
    {
        $email = $this->email->value();
        \assert($email !== '');

        return $email;
    }

    public function eraseCredentials(): void
    {
    }

    public function update(
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $avatar = null,
        ?string $email = null,
    ): void {
        if ($firstName !== null) {
            $this->firstName = $firstName;
        }
        if ($lastName !== null) {
            $this->lastName = $lastName;
        }
        if ($avatar !== null) {
            $this->avatar = $avatar;
        }
        if ($email !== null) {
            $this->email = new Email($email);
        }
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updatePassword(string $hashedPassword): void
    {
        $this->password = $hashedPassword;
        $this->updatedAt = new DateTimeImmutable();
    }
}
