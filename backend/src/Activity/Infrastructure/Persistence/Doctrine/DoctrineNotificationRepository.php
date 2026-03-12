<?php

declare(strict_types=1);

namespace App\Activity\Infrastructure\Persistence\Doctrine;

use App\Activity\Domain\Model\Notification;
use App\Activity\Domain\Repository\NotificationRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineNotificationRepository implements NotificationRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?Notification
    {
        return $this->entityManager->getRepository(Notification::class)->find($id);
    }

    /** @return list<Notification> */
    public function findByUser(string $userId, int $page = 1, int $perPage = 20): array
    {
        /** @var list<Notification> */
        return $this->entityManager->getRepository(Notification::class)
            ->createQueryBuilder('n')
            ->where('n.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('n.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function countByUser(string $userId): int
    {
        return (int) $this->entityManager->getRepository(Notification::class)
            ->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUnreadByUser(string $userId): int
    {
        return (int) $this->entityManager->getRepository(Notification::class)
            ->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.userId = :userId')
            ->andWhere('n.readAt IS NULL')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Notification $notification): void
    {
        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }
}
