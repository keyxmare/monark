<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Model\Team;
use App\Identity\Domain\Repository\TeamRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineTeamRepository implements TeamRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?Team
    {
        return $this->entityManager->getRepository(Team::class)->find($id);
    }

    public function findBySlug(string $slug): ?Team
    {
        return $this->entityManager->getRepository(Team::class)->findOneBy(['slug' => $slug]);
    }

    /** @return list<Team> */
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        return $this->entityManager->getRepository(Team::class)
            ->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function count(): int
    {
        return (int) $this->entityManager->getRepository(Team::class)
            ->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Team $team): void
    {
        $this->entityManager->persist($team);
        $this->entityManager->flush();
    }

    public function delete(Team $team): void
    {
        $this->entityManager->remove($team);
        $this->entityManager->flush();
    }
}
