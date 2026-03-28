<?php

declare(strict_types=1);

namespace App\Dependency\Infrastructure\Persistence\Doctrine;

use App\Dependency\Domain\Model\DependencyVersion;
use App\Dependency\Domain\Repository\DependencyVersionRepositoryInterface;
use App\Shared\Domain\ValueObject\PackageManager;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineDependencyVersionRepository implements DependencyVersionRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findByNameAndManager(string $dependencyName, PackageManager $packageManager): array
    {
        /** @var list<DependencyVersion> */
        return $this->entityManager->getRepository(DependencyVersion::class)
            ->createQueryBuilder('v')
            ->where('v.dependencyName = :name')
            ->andWhere('v.packageManager = :pm')
            ->setParameter('name', $dependencyName)
            ->setParameter('pm', $packageManager->value)
            ->orderBy('v.releaseDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findLatestByNameAndManager(string $dependencyName, PackageManager $packageManager): ?DependencyVersion
    {
        /** @var DependencyVersion|null */
        return $this->entityManager->getRepository(DependencyVersion::class)
            ->createQueryBuilder('v')
            ->where('v.dependencyName = :name')
            ->andWhere('v.packageManager = :pm')
            ->andWhere('v.isLatest = true')
            ->setParameter('name', $dependencyName)
            ->setParameter('pm', $packageManager->value)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByNameManagerAndVersion(string $dependencyName, PackageManager $packageManager, string $version): ?DependencyVersion
    {
        /** @var DependencyVersion|null */
        return $this->entityManager->getRepository(DependencyVersion::class)
            ->createQueryBuilder('v')
            ->where('v.dependencyName = :name')
            ->andWhere('v.packageManager = :pm')
            ->andWhere('v.version = :version')
            ->setParameter('name', $dependencyName)
            ->setParameter('pm', $packageManager->value)
            ->setParameter('version', $version)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(DependencyVersion $version): void
    {
        $this->entityManager->persist($version);
        $this->entityManager->flush();
    }

    public function clearLatestFlag(string $dependencyName, PackageManager $packageManager): void
    {
        $this->entityManager->getRepository(DependencyVersion::class)
            ->createQueryBuilder('v')
            ->update()
            ->set('v.isLatest', 'false')
            ->where('v.dependencyName = :name')
            ->andWhere('v.packageManager = :pm')
            ->setParameter('name', $dependencyName)
            ->setParameter('pm', $packageManager->value)
            ->getQuery()
            ->execute();
    }
}
