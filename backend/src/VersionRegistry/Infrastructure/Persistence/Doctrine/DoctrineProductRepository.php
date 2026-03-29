<?php

declare(strict_types=1);

namespace App\VersionRegistry\Infrastructure\Persistence\Doctrine;

use App\Shared\Domain\ValueObject\PackageManager;
use App\VersionRegistry\Domain\Model\Product;
use App\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineProductRepository implements ProductRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function findByNameAndManager(string $name, ?PackageManager $packageManager): ?Product
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->where('p.name = :name')
            ->setParameter('name', $name);

        if ($packageManager !== null) {
            $qb->andWhere('p.packageManager = :pm')->setParameter('pm', $packageManager->value);
        } else {
            $qb->andWhere('p.packageManager IS NULL');
        }

        /** @var Product|null */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findAll(): array
    {
        /** @var list<Product> */
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findStale(\DateTimeImmutable $before): array
    {
        /** @var list<Product> */
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->where('p.lastSyncedAt IS NULL OR p.lastSyncedAt < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->getResult();
    }

    public function findByNames(array $names): array
    {
        if ($names === []) {
            return [];
        }

        /** @var list<Product> */
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->where('p.name IN (:names)')
            ->setParameter('names', $names)
            ->getQuery()
            ->getResult();
    }

    public function save(Product $product): void
    {
        $this->em->persist($product);
        $this->em->flush();
    }
}
