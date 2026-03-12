<?php

declare(strict_types=1);

namespace App\Assessment\Infrastructure\Persistence\Doctrine;

use App\Assessment\Domain\Model\Attempt;
use App\Assessment\Domain\Repository\AttemptRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineAttemptRepository implements AttemptRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?Attempt
    {
        return $this->entityManager->getRepository(Attempt::class)->find($id);
    }

    /** @return list<Attempt> */
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        /** @var list<Attempt> */
        return $this->entityManager->getRepository(Attempt::class)
            ->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function count(): int
    {
        return (int) $this->entityManager->getRepository(Attempt::class)
            ->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Attempt $attempt): void
    {
        $this->entityManager->persist($attempt);
        $this->entityManager->flush();
    }
}
