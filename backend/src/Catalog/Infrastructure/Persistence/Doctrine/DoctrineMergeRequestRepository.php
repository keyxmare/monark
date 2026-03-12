<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Model\MergeRequest;
use App\Catalog\Domain\Model\MergeRequestStatus;
use App\Catalog\Domain\Repository\MergeRequestRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineMergeRequestRepository implements MergeRequestRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?MergeRequest
    {
        return $this->entityManager->getRepository(MergeRequest::class)->find($id);
    }

    /** @return list<MergeRequest> */
    public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20, ?MergeRequestStatus $status = null, ?string $author = null): array
    {
        $qb = $this->entityManager->getRepository(MergeRequest::class)
            ->createQueryBuilder('mr')
            ->where('mr.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('mr.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        if ($status !== null) {
            $qb->andWhere('mr.status = :status')
                ->setParameter('status', $status->value);
        }

        if ($author !== null) {
            $qb->andWhere('mr.author = :author')
                ->setParameter('author', $author);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByExternalIdAndProject(string $externalId, Uuid $projectId): ?MergeRequest
    {
        return $this->entityManager->getRepository(MergeRequest::class)
            ->findOneBy([
                'externalId' => $externalId,
                'project' => $projectId,
            ]);
    }

    public function countByProjectId(Uuid $projectId, ?MergeRequestStatus $status = null, ?string $author = null): int
    {
        $qb = $this->entityManager->getRepository(MergeRequest::class)
            ->createQueryBuilder('mr')
            ->select('COUNT(mr.id)')
            ->where('mr.project = :projectId')
            ->setParameter('projectId', $projectId);

        if ($status !== null) {
            $qb->andWhere('mr.status = :status')
                ->setParameter('status', $status->value);
        }

        if ($author !== null) {
            $qb->andWhere('mr.author = :author')
                ->setParameter('author', $author);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** @return list<MergeRequest> */
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        return $this->entityManager->getRepository(MergeRequest::class)
            ->createQueryBuilder('mr')
            ->orderBy('mr.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function count(): int
    {
        return (int) $this->entityManager->getRepository(MergeRequest::class)
            ->createQueryBuilder('mr')
            ->select('COUNT(mr.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(MergeRequest $mergeRequest): void
    {
        $this->entityManager->persist($mergeRequest);
        $this->entityManager->flush();
    }

    public function delete(MergeRequest $mergeRequest): void
    {
        $this->entityManager->remove($mergeRequest);
        $this->entityManager->flush();
    }
}
