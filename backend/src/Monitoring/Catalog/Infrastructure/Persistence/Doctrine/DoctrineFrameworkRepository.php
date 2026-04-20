<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Infrastructure\Persistence\Doctrine;

use App\Monitoring\Catalog\Domain\Model\Framework;
use App\Monitoring\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineFrameworkRepository implements FrameworkRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?Framework
    {
        return $this->entityManager->getRepository(Framework::class)->find($id);
    }

    /** @return list<Framework> */
    public function findAll(): array
    {
        /** @var list<Framework> */
        return $this->entityManager->getRepository(Framework::class)
            ->createQueryBuilder('f')
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Framework> */
    public function findByProjectId(Uuid $projectId): array
    {
        /** @var list<Framework> */
        return $this->entityManager->getRepository(Framework::class)
            ->createQueryBuilder('f')
            ->where('f.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByProjectIds(array $projectIds): array
    {
        if ($projectIds === []) {
            return [];
        }

        /** @var list<Framework> $rows */
        $rows = $this->entityManager->getRepository(Framework::class)
            ->createQueryBuilder('f')
            ->where('f.project IN (:projectIds)')
            ->setParameter('projectIds', $projectIds)
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($rows as $framework) {
            $out[$framework->getProject()->getId()->toRfc4122()][] = $framework;
        }

        return $out;
    }

    /** @return list<Framework> */
    public function findByName(string $name): array
    {
        /** @var list<Framework> */
        return $this->entityManager->getRepository(Framework::class)
            ->createQueryBuilder('f')
            ->where('f.name = :name')
            ->setParameter('name', $name)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByNameAndProjectId(string $name, Uuid $projectId): ?Framework
    {
        /** @var ?Framework */
        return $this->entityManager->getRepository(Framework::class)
            ->createQueryBuilder('f')
            ->where('f.name = :name')
            ->andWhere('f.project = :projectId')
            ->setParameter('name', $name)
            ->setParameter('projectId', $projectId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Framework $framework): void
    {
        $this->entityManager->persist($framework);
        $this->entityManager->flush();
    }

    public function delete(Framework $framework): void
    {
        $this->entityManager->remove($framework);
        $this->entityManager->flush();
    }

    public function deleteByProjectId(Uuid $projectId): void
    {
        $this->entityManager->getRepository(Framework::class)
            ->createQueryBuilder('f')
            ->delete()
            ->where('f.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->getQuery()
            ->execute();
    }
}
