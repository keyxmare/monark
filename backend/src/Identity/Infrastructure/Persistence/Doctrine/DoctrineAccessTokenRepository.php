<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Model\AccessToken;
use App\Identity\Domain\Repository\AccessTokenRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineAccessTokenRepository implements AccessTokenRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?AccessToken
    {
        return $this->entityManager->getRepository(AccessToken::class)->find($id);
    }

    /** @return list<AccessToken> */
    public function findByUser(Uuid $userId, int $page = 1, int $perPage = 20): array
    {
        /** @var list<AccessToken> */
        return $this->entityManager->getRepository(AccessToken::class)
            ->createQueryBuilder('t')
            ->where('t.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('t.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function countByUser(Uuid $userId): int
    {
        return (int) $this->entityManager->getRepository(AccessToken::class)
            ->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(AccessToken $accessToken): void
    {
        $this->entityManager->persist($accessToken);
        $this->entityManager->flush();
    }

    public function delete(AccessToken $accessToken): void
    {
        $this->entityManager->remove($accessToken);
        $this->entityManager->flush();
    }
}
