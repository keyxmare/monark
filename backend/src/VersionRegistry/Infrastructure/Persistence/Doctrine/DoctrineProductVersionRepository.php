<?php

declare(strict_types=1);

namespace App\VersionRegistry\Infrastructure\Persistence\Doctrine;

use App\Shared\Domain\ValueObject\PackageManager;
use App\VersionRegistry\Domain\Model\ProductVersion;
use App\VersionRegistry\Domain\Repository\ProductVersionRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineProductVersionRepository implements ProductVersionRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function findByNameAndManager(string $productName, ?PackageManager $packageManager): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('v')
            ->from(ProductVersion::class, 'v')
            ->where('v.productName = :name')
            ->setParameter('name', $productName)
            ->orderBy('v.releaseDate', 'DESC');

        if ($packageManager !== null) {
            $qb->andWhere('v.packageManager = :pm')->setParameter('pm', $packageManager->value);
        } else {
            $qb->andWhere('v.packageManager IS NULL');
        }

        /** @var list<ProductVersion> */
        return $qb->getQuery()->getResult();
    }

    public function findLatestByNameAndManager(string $productName, ?PackageManager $packageManager): ?ProductVersion
    {
        $qb = $this->em->createQueryBuilder()
            ->select('v')
            ->from(ProductVersion::class, 'v')
            ->where('v.productName = :name')
            ->andWhere('v.isLatest = true')
            ->setParameter('name', $productName);

        if ($packageManager !== null) {
            $qb->andWhere('v.packageManager = :pm')->setParameter('pm', $packageManager->value);
        } else {
            $qb->andWhere('v.packageManager IS NULL');
        }

        /** @var ProductVersion|null */
        return $qb->getQuery()->setMaxResults(1)->getOneOrNullResult();
    }

    public function findByNameManagerAndVersion(string $productName, ?PackageManager $packageManager, string $version): ?ProductVersion
    {
        $qb = $this->em->createQueryBuilder()
            ->select('v')
            ->from(ProductVersion::class, 'v')
            ->where('v.productName = :name')
            ->andWhere('v.version = :version')
            ->setParameter('name', $productName)
            ->setParameter('version', $version);

        if ($packageManager !== null) {
            $qb->andWhere('v.packageManager = :pm')->setParameter('pm', $packageManager->value);
        } else {
            $qb->andWhere('v.packageManager IS NULL');
        }

        /** @var ProductVersion|null */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findKnownVersionStrings(string $productName, ?PackageManager $packageManager): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('v.version')
            ->from(ProductVersion::class, 'v')
            ->where('v.productName = :name')
            ->setParameter('name', $productName);

        if ($packageManager !== null) {
            $qb->andWhere('v.packageManager = :pm')->setParameter('pm', $packageManager->value);
        } else {
            $qb->andWhere('v.packageManager IS NULL');
        }

        /** @var list<array{version: string}> $rows */
        $rows = $qb->getQuery()->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['version']] = true;
        }

        return $map;
    }

    public function save(ProductVersion $version): void
    {
        $this->em->persist($version);
        $this->em->flush();
    }

    public function saveMany(ProductVersion ...$versions): void
    {
        foreach ($versions as $version) {
            $this->em->persist($version);
        }
        $this->em->flush();
    }

    public function clearLatestFlag(string $productName, ?PackageManager $packageManager): void
    {
        $qb = $this->em->createQueryBuilder()
            ->update(ProductVersion::class, 'v')
            ->set('v.isLatest', 'false')
            ->where('v.productName = :name')
            ->setParameter('name', $productName);

        if ($packageManager !== null) {
            $qb->andWhere('v.packageManager = :pm')->setParameter('pm', $packageManager->value);
        } else {
            $qb->andWhere('v.packageManager IS NULL');
        }

        $qb->getQuery()->execute();
    }
}
