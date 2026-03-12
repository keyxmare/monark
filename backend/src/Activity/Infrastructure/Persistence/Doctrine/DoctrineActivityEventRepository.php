<?php

declare(strict_types=1);

namespace App\Activity\Infrastructure\Persistence\Doctrine;

use App\Activity\Domain\Model\ActivityEvent;
use App\Activity\Domain\Repository\ActivityEventRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineActivityEventRepository implements ActivityEventRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?ActivityEvent
    {
        return $this->entityManager->getRepository(ActivityEvent::class)->find($id);
    }

    /** @return list<ActivityEvent> */
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        /** @var list<ActivityEvent> */
        return $this->entityManager->getRepository(ActivityEvent::class)
            ->createQueryBuilder('e')
            ->orderBy('e.occurredAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function count(): int
    {
        return (int) $this->entityManager->getRepository(ActivityEvent::class)
            ->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(ActivityEvent $event): void
    {
        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }
}
