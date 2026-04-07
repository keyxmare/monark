<?php

declare(strict_types=1);

namespace App\History\Infrastructure\Persistence\Doctrine;

use App\History\Domain\Model\ProjectDebtSnapshot;
use App\History\Domain\Repository\ProjectDebtSnapshotRepositoryInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineProjectDebtSnapshotRepository implements ProjectDebtSnapshotRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function findById(Uuid $id): ?ProjectDebtSnapshot
    {
        return $this->em->getRepository(ProjectDebtSnapshot::class)->find($id);
    }

    public function findByProjectAndCommit(Uuid $projectId, string $commitSha): ?ProjectDebtSnapshot
    {
        /** @var ProjectDebtSnapshot|null */
        return $this->em->getRepository(ProjectDebtSnapshot::class)
            ->createQueryBuilder('s')
            ->andWhere('s.projectId = :projectId')
            ->andWhere('s.commitSha = :commitSha')
            ->setParameter('projectId', $projectId)
            ->setParameter('commitSha', $commitSha)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByProjectBetween(Uuid $projectId, ?DateTimeImmutable $from, ?DateTimeImmutable $to): array
    {
        $qb = $this->em->getRepository(ProjectDebtSnapshot::class)
            ->createQueryBuilder('s')
            ->andWhere('s.projectId = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('s.snapshotDate', 'ASC');

        if ($from !== null) {
            $qb->andWhere('s.snapshotDate >= :from')->setParameter('from', $from);
        }
        if ($to !== null) {
            $qb->andWhere('s.snapshotDate <= :to')->setParameter('to', $to);
        }

        /** @var list<ProjectDebtSnapshot> */
        return $qb->getQuery()->getResult();
    }

    public function save(ProjectDebtSnapshot $snapshot): void
    {
        $this->em->persist($snapshot);
        $this->em->flush();
    }
}
