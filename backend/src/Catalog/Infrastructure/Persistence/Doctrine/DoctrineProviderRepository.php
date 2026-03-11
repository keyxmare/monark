<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Repository\ProviderRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineProviderRepository implements ProviderRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?Provider
    {
        return $this->entityManager->getRepository(Provider::class)->find($id);
    }

    /** @return list<Provider> */
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        return $this->entityManager->getRepository(Provider::class)
            ->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function count(): int
    {
        return (int) $this->entityManager->getRepository(Provider::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Provider $provider): void
    {
        $this->entityManager->persist($provider);
        $this->entityManager->flush();
    }

    public function remove(Provider $provider): void
    {
        $this->entityManager->remove($provider);
        $this->entityManager->flush();
    }
}
